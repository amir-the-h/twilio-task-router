<?php

namespace App\Helpers;

use Exception;
use Illuminate\Support\Facades\Log;
use libphonenumber;

class VoipHelper
{

  /**
   * @var string[]
   */
  const NORTH_AMERICA_COUNTRY_CODES = [
    'US', 'AG', 'AI', 'AS', 'BB', 'BM', 'BS',
    'DM', 'DO', 'GD', 'GU', 'JM', 'KN', 'KY',
    'LC', 'MP', 'MS', 'PR', 'SX', 'TC', 'TT',
    'VC', 'VG', 'VI', 'UM', 'CA',
  ];

  /**
   * Tier 1 International Codes
   *
   * @var string[]
   */
  const TIER_1_INTERNATIONAL_CODES = [
    'US', 'CA', 'PR', 'AS', 'GU',
  ];

  /**
   * Checks if number is a shortcode
   *
   * @param $phone_number
   * @return bool
   */
  public static function isPossibleShortCode($phone_number): bool
  {
    // get the library ready
    $phoneUtil = libphonenumber\PhoneNumberUtil::getInstance();

    // Get an instance of ShortNumberInfo
    $shortNumberUtil = libphonenumber\ShortNumberInfo::getInstance();

    try {
      // Parse number with US country code and keep raw input
      $phone_number_obj = $phoneUtil->parseAndKeepRawInput($phone_number, 'US');

      if ($shortNumberUtil->isValidShortNumberForRegion($phone_number_obj, 'US')) {
        return true;
      }
    } catch (Exception $ex) {
      return false;
    }

    return false;
  }

  /**
   * Detects the country based on phone number
   *
   * @param array $countries
   * @param string $phone_number
   * @return false|string
   * @throws libphonenumber\NumberParseException
   */
  protected static function detectCountryInPhoneNumber(array $countries, string $phone_number)
  {
    // get the library ready
    $phoneUtil = libphonenumber\PhoneNumberUtil::getInstance();

    foreach ($countries as $country) {
      $number = $phoneUtil->parse($phone_number, $country);
      $is_possible = $phoneUtil->isPossibleNumber($number);
      if ($is_possible) {
        if ($phoneUtil->isValidNumberForRegion($number, $country)) {
          return $country;
        }
      }
    }

    return false;
  }

  /**
   * checks if a number is a possible North American number
   *
   * @param $phone_number
   * @return string
   */
  public static function getLocaleIfPhoneNumberIsFromNorthAmerica($phone_number)
  {
    try {
      return self::detectCountryInPhoneNumber(self::NORTH_AMERICA_COUNTRY_CODES, $phone_number);
    } catch (Exception $e) {
      Log::info('Cannot guess locale is from North America', ['number' => $phone_number, 'code' => $e->getCode(), 'message' => $e->getMessage()]);

      return false;
    }
  }

  /**
   * checks if a number is a possible Great Britain or Australian number
   *
   * @param $phone_number
   * @return string
   */
  public static function getLocaleIfPhoneNumberIsFromGreatBritainOrAustralia($phone_number)
  {
    $valid_countries = ['GB', 'AU'];

    try {
      return self::detectCountryInPhoneNumber($valid_countries, $phone_number);
    } catch (Exception $e) {
      Log::info('Cannot guess locale is from Great Britain', ['number' => $phone_number, 'code' => $e->getCode(), 'message' => $e->getMessage()]);

      return false;
    }
  }
  /**
   * Guess locale from a phone number
   *
   * @param $phone_number
   * @return bool|string
   */
  public static function guessLocale($phone_number)
  {
    try {
      // get the library ready
      $phoneUtil = libphonenumber\PhoneNumberUtil::getInstance();

      // handle North America as a special case
      $north_america_locale = self::getLocaleIfPhoneNumberIsFromNorthAmerica($phone_number);
      if ($north_america_locale) {
        return $north_america_locale;
      }

      // will add + to phone number and check again
      if (strpos($phone_number, '+') === false) {
        $north_america_locale = self::getLocaleIfPhoneNumberIsFromNorthAmerica('+' . $phone_number);
        if ($north_america_locale) {
          return $north_america_locale;
        }
      }

      // will add +1 to phone number and check again
      if (strpos($phone_number, '+') === false) {
        $north_america_locale = self::getLocaleIfPhoneNumberIsFromNorthAmerica('+1' . $phone_number);
        if ($north_america_locale) {
          return $north_america_locale;
        }
      }

      // handle GB & AU as a special case
      $english_locale = self::getLocaleIfPhoneNumberIsFromGreatBritainOrAustralia($phone_number);
      if ($english_locale) {
        return $english_locale;
      }

      // if we reached here then it's definitely not a US or CA number according to libphonenumber
      // let's check for international locales

      // phoneUtil can only parse international looking numbers (numbers that starts with +)
      if (strpos($phone_number, '+') === false) {
        $phone_number = '+' . $phone_number;
      }

      $number = $phoneUtil->parse($phone_number);
      $locale = $phoneUtil->getRegionCodeForNumber($number);

      if (!$locale) {
        Log::info('Cannot guess locale from the provided phone number', ['number' => $phone_number]);

        return false;
      }

      return (string) $locale;
    } catch (Exception $e) {
      Log::info('Cannot guess locale from the provided phone number', ['number' => $phone_number, 'code' => $e->getCode(), 'message' => $e->getMessage()]);

      return false;
    }
  }

  /**
   * Transform a phone number to E.164 format defaulting on US.
   *
   * @param $phone_number
   * @param string $format
   * @param null $locale
   * @param bool $include_suffix
   * @return null|string E.164 formatted phone number.
   */
  public static function fixPhone($phone_number, string $format = 'E164', $locale = null, bool $include_suffix = false)
  {
    // remove whitespace from inside the string
    $phone_number = preg_replace('/\s/', '', $phone_number);

    // remove # from phone number
    $phone_number = str_replace('#', '', $phone_number);

    // sanity check
    if (empty($phone_number)) {
      return null;
    }

    // remove . from phone number
    $phone_number = str_replace('.', '', $phone_number);

    // phone number length
    $phone_number_length = strlen($phone_number);

    // Use substr() and strpos() functions to remove
    // portion of string after certain character (w => wait)
    $suffix = '';
    $pos = strpos($phone_number, 'w');
    if ($pos !== false) {
      $suffix = trim(substr($phone_number, $pos, $phone_number_length - 1));
      $phone_number = trim(substr($phone_number, 0, $pos));
    }

    // get the library ready
    $phoneUtil = libphonenumber\PhoneNumberUtil::getInstance();

    // find locale
    if (empty($locale)) {
      $locale = self::guessLocale($phone_number);
    }

    if (!$locale) {
      return false;
    }

    // sanity checks
    switch ($format) {
      case 'E164':
        $format = libphonenumber\PhoneNumberFormat::E164;
        break;
      case 'national':
        $format = libphonenumber\PhoneNumberFormat::NATIONAL;
        break;
      case 'international':
        $format = libphonenumber\PhoneNumberFormat::INTERNATIONAL;
        break;
      default:
    }

    if (!$phoneUtil->isPossibleNumber($phone_number, $locale)) {
      Log::notice('Invalid phone number supplied', ['number' => $phone_number, 'locale' => $locale]);

      return false;
    }

    // try to return false when we got some invalid number like empty strings
    try {
      $numberProto = $phoneUtil->parse($phone_number, $locale);
      // done
      $formatted_phone_number = $phoneUtil->format($numberProto, $format);

      // if we have to include suffix
      if ($include_suffix) {
        $formatted_phone_number = $formatted_phone_number . $suffix;
      }

      $formatted_phone_number_length = strlen($formatted_phone_number);

      // sanity check
      if ($formatted_phone_number_length <= 9) {
        Log::notice('Short phone number was generated', ['number' => $formatted_phone_number, 'locale' => $locale]);

        return false;
      }

      return $formatted_phone_number;
    } catch (Exception $e) {
      Log::error('Invalid phone number supplied', ['number' => $phone_number, 'locale' => $locale, 'code' => $e->getCode(), 'message' => $e->getMessage()]);

      return false;
    }
  }

  /**
   * Get area code base on phone number
   *
   * @param $phone_number
   * @param null $country
   * @return string
   */
  public static function getAreaCode($phone_number, $country = null)
  {
    $phone_number = self::fixPhone($phone_number, 'E164', $country);

    return intval(substr($phone_number, 2, 3));
  }
}
