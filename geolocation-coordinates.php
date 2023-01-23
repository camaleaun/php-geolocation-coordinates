<?php
/**
 * Coordinates class
 *
 * Handles geolocation coordinates Latitude and Longitude values and output in DD or DMS formats.
 *
 * @version 1.0.0
 */

namespace Camaleaun;

/**
 * GeolocationCoordinates Class.
 */
class GeolocationCoordinates implements \JsonSerializable
{

    /**
     * Latitude.
     *
     * @var
     */
    protected float $latitude = 0;

    /**
     * Longitude.
     *
     * @var
     */
    protected float $longitude = 0;

    /**
     * Constructor
     *
     * Accepted formats:
     *  - Decimal degrees (DD)
     *    - Single parameter as string with Latitude and Longitude in this order separate by comma. Both values in
     *      string must integer or float using dot as decimal separator. Whitespace are ignored.
     *      e.g. new Coordinates('49.202442, 16.615052'); or new Coordinates('49.202442,16.615052');
     *    - Single parameter as array with Latitude and Longitude in this order. Both values must integer or float
     *      using dot decimal and separate by comma. Whitespace are ignored.
     *      e.g. new Coordinates('49.202442, 16.615052'); or new Coordinates('49.202442,16.615052');
     *    - Two parameter Latitude and Longitude in this order as string, integer or float.
     *      e.g. new Coordinates(49.202442, 16.615052);
     *  - Degrees, minutes, and seconds (DMS)
     *    - Single parameter as string. With cardinal directions initials notation (N, S, E, and W) or without this
     *      notation and negative when South/East or positive North/West.
     *      e.g. new Coordinates('49°12\'8.8"N 16°36\'54.2"E'); or new Coordinates('49°12\'08.8" 16°36\'54.2"');
     *    - Two parameter Latitude and Longitude as string in this order using positive/negative or unordened using
     *      N/S/E/W.
     *      e.g. new Coordinates('49°12\'08.8"N', '16°36\'54.2"E'); or new Coordinates('49°12\'08.8"' '16°36\'54.2"');
     *
     * @param string|array     $coordinates_or_latitude Latitude and Longitude or Latitude.
     * @param string|int|float $longitude               Latitude and Longitude. Optional, case use coordinates in
     *                                                  $coordinates_or_latitude
     */
    public function __construct($coordinates_or_latitude, $longitude = null)
    {
        if (null === $longitude) {
            list($this->latitude, $this->longitude) = $this->extractCoordinates($coordinates_or_latitude);
        } else {
            $this->latitude = current($this->extractCoordinates((string)$coordinates_or_latitude));
            $this->longitude = current($this->extractCoordinates((string)$longitude));
        }
    }

    /**
     * When converted to JSON.
     *
     * @return object
     */
    public function jsonSerialize()
    {
        return (Object) array(
            'latitude' => $this->latitude,
            'longitude' => $this->longitude,
        );
    }

    /**
     * Output an ISO 8601 date string in local (WordPress) timezone.
     *
     * @since  1.0.0
     * @return string
     */
    public function __toString()
    {
        return $this->format('dd');
    }

    /**
     * Check coordinates do DMS format.
     *
     * @since  1.0.0
     * @param  string $value Coordinates or point.
     * @return string
     */
    private function isDMS($coordinates)
    {
        $coordinates = $this->sanitize($coordinates);
        return preg_match('/[dms]/', $coordinates);
    }

    /**
     * Extract latitude and longitude from DD or DMS.
     *
     * @since  1.0.0
     * @param  string $value Coordinates or point.
     * @return string
     */
    private function extractCoordinates($coordinates)
    {
        if ($this->isDMS($coordinates)) {
            $coordinates = $this->extractDMS($coordinates);
        } else {
            $coordinates = $this->extractDD($coordinates);
        }
        return $coordinates;
    }

    /**
     * Extract latitude and longitude from DMS.
     *
     * @since  1.0.0
     * @param  string $value Coordinates or point.
     * @return string
     */
    private function extractDMS($coordinates)
    {
        $coordinates = $this->sanitize($coordinates);
        preg_match_all('/[\ddmsc\.nsew-]+/', $coordinates, $points);
        if ($points) {
            $points = current($points);
        }
        $points = array_slice($points, 0, 2);
        $latitudes = array();
        $longitudes = array();
        foreach ($points as $i => $point) {
            if (preg_match('/[nsew]/', $point)) {
                $point = str_replace('-', '', $point);
            }
            if (preg_match('/[ns]/', $point)) {
                if (str_contains($point, 's')) {
                    $point = '-' . $point;
                }
                $point = preg_replace('/[ns]/', '', $point);
                $latitudes[] = $point;
                unset($points[$i]);
            }
            if (preg_match('/[ew]/', $point)) {
                if (str_contains($point, 'w')) {
                    $point = '-' . $point;
                }
                $point = preg_replace('/[ew]/', '', $point);
                $longitudes[] = $point;
                unset($points[$i]);
            }
        }
        if (!$latitudes && $points) {
            $latitudes = $points;
            $points = array();
        }
        $points = array_merge(
            $latitudes,
            $longitudes,
            $points
        );
        foreach ($points as &$point) {
            $point = str_replace('c', 's', $point);
            preg_match_all('/[\d\.?\d*-]+[dms]/', $point, $parts);
            if ($parts) {
                $parts = current($parts);
            }
            $dms = array(
                'd' => 0,
                'm' => 0,
                's' => 0,
            );
            foreach ($parts as $part) {
                preg_match('/[dms]/', $part, $unit);
                if ($unit) {
                    $unit = current($unit);
                }
                $part = preg_replace('/[dms]/', '', $part);
                $part = (float)$part;
                if ('s' !== $unit) {
                    $part = (int)floor($part);
                }
                $dms[$unit] = $part;
            }
            list($degrees, $minutes, $seconds) = array_values($dms);
            $point = $degrees + ( ( ( $minutes * 60 ) + ( $seconds ) ) / 3600 );
        }
        return array_filter($points);
    }

    /**
     * Extract latitude and longitude from DD.
     *
     * @since  1.0.0
     * @param  string $value Coordinates or point.
     * @return string
     */
    private function extractDD($coordinates)
    {
        if (empty($coordinates)) {
            $coordinates = array();
        }
        if (is_object($coordinates)) {
            $coordinates = (array)$coordinates;
        }
        if (is_string($coordinates)) {
            preg_match_all('/(-?\d+\.?\d*)/', $coordinates, $output);
            if ($output && $output[1]) {
                $coordinates = $output[1];
            } else {
                $coordinates = array();
            }
        }
        if (is_array($coordinates)) {
            $coordinates = array_values($coordinates);
            $coordinates = array_merge(
                $coordinates,
                array(0, 0)
            );
            $coordinates = array_slice($coordinates, 0, 2);
            foreach ($coordinates as &$position) {
                $position = (float) preg_replace('/(-?\d+\.?\d*)/', '$1', $position);
            }
        }
        return array_filter($coordinates);
    }

    /**
     * Sanitize coordinates or point.
     *
     * @since  1.0.0
     * @param  string $value Coordinates or point.
     * @return string
     */
    private function sanitize($value)
    {
        $value = str_replace(array('°',"'",'"'), array('d','m','c'), strtolower($value));
        return preg_replace('/[^\d\.,\sdmcnsew-]/', '', $value);
    }

    /**
     * Converts DD to DMS
     *
     * @since  1.0.0
     * @param  string $dd       DD coordinates.
     * @param  int    $decimals Sets the number of decimal digits.
     * @return string DMS coordinates.
     */
    private function dms($dd, $decimals = 3)
    {
        $dd = abs($dd);

        $decimals = absint($decimals);

        $vars = explode('.', $dd);
        $deg = intval($vars[0]);
        $tempma = '0.' . $vars[1];

        $tempma = $tempma * 3600;
        $min = floor($tempma / 60);
        $sec = $tempma - ( $min * 60 );

        $sec = round($sec, $decimals);

        return sprintf('%d°%d\'%s"', $deg, $min, $sec);
    }

    /**
     * Output DD or DMS format.
     *
     * DD: Decimal degrees
     * DMS: Degrees, minutes, and seconds
     *
     * @since  1.0.0
     * @param  string $format   DD or DMS. Default DD.
     * @param  int    $decimals Sets the number of decimal digits.
     * @return string DD 49.202442, 16.615052 DMS 49°12'08.8"N 16°36'54.2"E
     */
    public function format($format = 'dd', $decimals = null)
    {
        $format = $this->sanitizeFormat($format);

        if ('dms' === $format) {
            if (is_null($decimals)) {
                $decimals = 3;
            }
            $latitude = $this->dms($this->latitude, $decimals) . ( $this->latitude < 0 ? 'S' : 'N' );
            $longitude = $this->dms($this->longitude, $decimals) . ( $this->longitude < 0 ? 'W' : 'E' );

            $output = sprintf('%s %s', $latitude, $longitude);
        } else {
            if (is_null($decimals)) {
                $decimals = 6;
            }
            $latitude = $this->latitude;
            $longitude = $this->longitude;
            if (is_integer($decimals)) {
                $latitude = round($latitude, $decimals);
                $longitude = round($longitude, $decimals);
            }
            $output = sprintf('%s, %s', $latitude, $longitude);
        }

        return $output;
    }

    /**
     * Sanitize format.
     *
     * @since  1.0.0
     * @param  string $format Coordinates format.
     * @return string Sanitize format.
     */
    private function sanitizeFormat($format)
    {
        $format = strtolower($format);
        if ('dms' !== $format) {
            $format = 'dd';
        }
        return $format;
    }
}
