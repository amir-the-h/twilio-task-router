<script setup>
import { ref, computed, toRaw, onMounted } from "vue";
import { Device } from "twilio-client/dist/twilio.min.js";
const workerSid = "WK99de8bbc4445579e41c00b403b4aba08";
const workerName = "amir";
const state = ref({
  device: null,
  worker: null,
  worker_token: null,
  webrtc_token: null,
  activities: [],
  reservations: [],
  activeTask: null,
  loadings: {
    login: false,
    activities: false,
  },
});

const status = computed(() => {
  if (state.value.loadings.login) {
    return "Logging in...";
  }

  if (!state.value.worker_token && !state.value.worker) {
    return "Authenticating with Twilio...";
  }

  if (!state.value.device) {
    return "Initializing WebRTC...";
  }

  if (state.value.worker) {
    if (state.value.worker.friendlyName) {
      return `Connected ${state.value.worker.friendlyName}`;
    }

    return "Connected";
  }

  return "Not connected";
});

const findCurrentActivity = () => {
  const worker = toRaw(state.value.worker);
  if (!worker) {
    return null;
  }

  // find the activity name from the activities array
  const activities = toRaw(state.value.activities);
  return activities.find(
    (activity) => activity.sid === state.value.worker.activitySid
  );
};

const activityName = computed(() => {
  const currentActivity = findCurrentActivity();
  return currentActivity ? currentActivity.friendlyName : "Activities";
});

const defaultActivityIndex = computed(() => {
  const currentActivity = findCurrentActivity();
  return currentActivity ? currentActivity.sid : null;
});

const login = () => {
  state.value.loadings.login = true;

  // make a post request to /api/v1/workers/login
  return new Promise((resolve, reject) => {
    axios
      .post("/api/v1/workers/login", {
        workerSid,
        workerName,
      })
      .then((res) => {
        state.value.loadings.login = false;
        state.value.worker_token = res.data.worker_token;
        state.value.webrtc_token = res.data.webrtc_token;
        resolve(res);
      })
      .catch((err) => {
        state.value.loadings.login = false;
        reject(err);
      });
  });
};

const setupWorker = () => {
  let worker = new Twilio.TaskRouter.Worker(state.value.worker_token);

  worker.on("ready", (readyWorker) => {
    state.value.worker = mergeWorkers(readyWorker);
    console.log(`Worker ${readyWorker.sid} is now ready for work`);
  });

  worker.on("reservation.created", (reservation) => {
    addOrUpdateReservation(reservation);
    console.log(
      `Reservation ${reservation.sid} has been created for ${worker.workerSid}`
    );
  });

  worker.on("reservation.updated", (reservation) => {
    addOrUpdateReservation(reservation);
  });

  worker.on("reservation.canceled", (reservation) => {
    removeReservation(reservation);
  });

  worker.on("reservation.completed", (reservation) => {
    removeReservation(reservation);
  });

  state.value.worker = worker;
};

const setupWebrtc = () => {
  const token = toRaw(state.value.webrtc_token);
  const device = new Device();
  device.setup(token, {
    debug: true,
    answerOnBridge: true,
    codecPreferences: ["opus", "pcmu"],
  });
  device.audio.availableInputDevices;
  device.on("ready", () => {
    console.log("WebRTC is ready");
  });
  device.on("incoming", (connection) => {
    console.log("Connection", connection);
    console.log("Call", connection.call_sid);
    connection.accept();
  });
  device.on("disconnect", (connection) => {
    completeTask();
    console.log("Disconnected", connection);
  });
  state.value.device = device;
};

const fetchActivities = () => {
  const worker = toRaw(state.value.worker);
  state.value.loadings.activities = true;
  worker.activities.fetch(function (error, activityList) {
    if (error) {
      console.log(error.code);
      console.log(error.message);
      state.value.loadings.activities = false;
      return;
    }
    const data = activityList.data;
    for (let i = 0; i < data.length; i++) {
      state.value.activities.push({
        sid: data[i].sid,
        friendlyName: data[i].friendlyName,
        available: data[i].available,
      });
    }
    state.value.loadings.activities = false;
  });
};

const changeActivity = (activitySid) => {
  state.value.loadings.activities = true;
  const worker = toRaw(state.value.worker);
  worker.update("ActivitySid", activitySid, function (error, worker) {
    if (error) {
      console.log(error.code);
      console.log(error.message);
    } else {
      state.value.worker = mergeWorkers(worker);
    }
    state.value.loadings.activities = false;
  });
};

const addOrUpdateReservation = (reservation) => {
  const reservations = toRaw(state.value.reservations);
  const existingReservation = reservations.find(
    (rv) => rv.sid === reservation.sid
  );
  if (existingReservation) {
    const index = reservations.indexOf(existingReservation);
    state.value.reservations[index] = reservations;
  } else {
    state.value.reservations.push(reservation);
  }
};
const removeReservation = (reservation) => {
  const reservations = toRaw(state.value.reservations);
  const existingReservation = reservations.find(
    (rv) => rv.sid === reservation.sid
  );
  if (existingReservation) {
    const index = reservations.indexOf(existingReservation);
    // remove the reservation from the array
    state.value.reservations.splice(index, 1);
    if (
      state.value.activeTask &&
      existingReservation.task.sid === state.value.activeTask.sid
    ) {
      completeTask();
    }
  }
};

const actionReservation = (reservation, action) => {
  const rawReservation = toRaw(reservation);
  if (action === "accept") {
    rawReservation.conference(
      "+18186000607",
      null,
      null,
      "client:amir",
      function (error, reservation) {
        if (error) {
          console.log(error.code);
          console.log(error.message);
          return;
        }
        state.value.activeTask = reservation.task;
        console.log("conference initiated");
      }
    );
  } else if (action === "reject") {
    rawReservation.reject(function (error, rv) {
      if (error) {
        console.log(error.code);
        console.log(error.message);
        return;
      }
      console.log("reservation rejected");
      for (const property in rv) {
        console.log(property + " : " + rv[property]);
      }
    });
  }
};

const completeTask = () => {
  const activeTask = toRaw(state.value.activeTask);
  const worker = toRaw(state.value.worker);
  console.log(worker, activeTask);
  if (activeTask) {
    worker.completeTask(activeTask.sid, function (error, completedTask) {
      if (error) {
        console.log(error.code);
        console.log(error.message);
        return;
      }
      console.log("Completed Task: " + completedTask.assignmentStatus);

      state.value.activeTask = null;

      toRaw(state.value.device).disconnectAll();
    });
  }
};

const mergeWorkers = (newWorker) => {
  const worker = toRaw(state.value.worker);
  for (const property in newWorker) {
    worker[property] = newWorker[property];
  }
  return worker;
};

onMounted(() => {
  login().then(() => {
    setupWorker();
    fetchActivities();
    setupWebrtc();
  });
});
</script>

<style>
.flex-grow {
  flex-grow: 1;
}
.card-header {
  display: flex;
  justify-content: space-between;
  align-items: center;
}

.text {
  font-size: 14px;
}

.item {
  margin-bottom: 18px;
}
</style>

<template>
  <el-container>
    <el-header>
      <el-menu
        :default-active="defaultActivityIndex"
        class="el-menu-demo"
        mode="horizontal"
        @select="changeActivity"
        :ellipsis="false"
      >
        <el-menu-item>
          <h2>{{ status }}</h2>
        </el-menu-item>
        <div class="flex-grow" />
        <el-sub-menu index="1" :disabled="state.loadings.activities">
          <template #title>{{ activityName }}</template>
          <el-menu-item
            v-for="activity in state.activities"
            :index="activity.sid"
            :key="activity.sid"
            >{{ activity.friendlyName }}</el-menu-item
          >
        </el-sub-menu>
      </el-menu>
    </el-header>
    <el-main style="margin-top: 20px">
      <el-row class="row-bg" justify="space-between">
        <el-col :span="12" :push="6">
          <div class="grid-content">
            <el-card
              class="box-card"
              v-for="reservation in state.reservations"
              :key="reservation.sid"
            >
              <template #header>
                <div class="card-header">
                  <span>Card name</span>
                  <div>
                    <el-button
                      v-if="!state.activeTask"
                      class="button"
                      text
                      type="success"
                      :disabled="
                        state.activeReservation &&
                        state.activeReservation.sid === reservation.sid
                      "
                      @click="actionReservation(reservation, 'accept')"
                    >
                      Accept
                    </el-button>
                    <el-button
                      v-if="!state.activeTask"
                      class="button"
                      text
                      type="danger"
                      @click="actionReservation(reservation, 'reject')"
                    >
                      Reject
                    </el-button>
                    <el-button
                      v-else
                      class="button"
                      text
                      type="warning"
                      @click="completeTask()"
                    >
                      Finish
                    </el-button>
                  </div>
                </div>
              </template>
              <div class="text item">
                Status:
                <el-tag size="small">{{
                  reservation.reservationStatus
                }}</el-tag>
              </div>
              <div class="text item">
                Channel:
                <el-tag size="small">{{
                  reservation.task.taskChannelUniqueName
                }}</el-tag>
              </div>
              <div class="text item">
                Area Code:
                <el-tag size="small">{{
                  reservation.task.attributes.area_code
                }}</el-tag>
              </div>
            </el-card>
          </div>
        </el-col>
      </el-row>
    </el-main>
  </el-container>
</template>