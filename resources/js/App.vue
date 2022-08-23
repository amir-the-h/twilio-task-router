<script setup>
import { ref, computed, toRaw, onMounted } from "vue";
const workerSid = "WK99de8bbc4445579e41c00b403b4aba08";
const state = ref({
  worker: null,
  token: null,
  activities: [],
  loadings: {
    login: false,
    activities: false,
  },
});

const status = computed(() => {
  if (state.value.loadings.login) {
    return "Logging in...";
  }
  if (state.value.token !== null && state.value.worker === null) {
    return "Authenticating with Twilio...";
  }
  return state.value.worker !== null ? "Connected" : "Not connected";
});

const activityName = computed(() => {
  const worker = toRaw(state.value.worker);
  if (worker === null) {
    return "Activities";
  }

  // find the activity name from the activities array
  const activity = state.value.activities.find(
    (activity) => activity.sid === state.value.worker.activitySid
  );

  return activity ? activity.friendlyName : "Activities";
});

const login = () => {
  state.value.loadings.login = true;

  // make a post request to /api/v1/workers/login
  return new Promise((resolve, reject) => {
    axios
      .post("/api/v1/workers/login", {
        workerSid,
      })
      .then((res) => {
        state.value.loadings.login = false;
        state.value.token = res.data.token;
        resolve(res);
      })
      .catch((err) => {
        state.value.loadings.login = false;
        reject(err);
      });
  });
};

const setupWorker = () => {
  state.value.worker = new Twilio.TaskRouter.Worker(state.value.token);

  state.value.worker.on("ready", (readyWorker) => {
    state.value.worker.activitySid = readyWorker.activitySid;
    console.log(`Worker ${readyWorker.sid} is now ready for work`);
  });

  state.value.worker.on("reservationCreated", (reservation) => {
    console.log(
      `Reservation ${reservation.sid} has been created for ${state.value.worker.sid}`
    );
    console.log(`Task attributes are: ${reservation.task.attributes}`);

    reservation.on("accepted", (acceptedReservation) => {
      console.log(`Reservation ${acceptedReservation.sid} was accepted.`);
    });

    reservation
      .accept()
      .then((acceptedReservation) => {
        console.log(`Reservation status is ${acceptedReservation.status}`);
      })
      .catch((err) => {
        console.log(`Error: ${err}`);
      });
  });
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
      state.value.worker = worker;
    }
    state.value.loadings.activities = false;
  });
};

onMounted(() => {
  login().then(() => {
    setupWorker();
    fetchActivities();
  });
});
</script>

<style>
.flex-grow {
  flex-grow: 1;
}
</style>

<template>
  <el-container>
    <el-header>
      <el-menu
        class="el-menu-demo"
        mode="horizontal"
        @select="changeActivity"
        :ellipsis="false"
      >
        <el-menu-item index="0" disabled
          ><h2>{{ status }}</h2></el-menu-item
        >
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
          <div class="grid-content">Content</div>
        </el-col>
      </el-row>
    </el-main>
  </el-container>
</template>