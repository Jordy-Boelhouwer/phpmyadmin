<?php
/**
 * Handles the visualization of GIS MULTIPOLYGON objects.
 *
 * @package phpMyAdmin
 */
class PMA_GIS_Multipolygon extends PMA_GIS_Geometry
{
    // Hold the singleton instance of the class
    private static $_instance;

    /**
     * A private constructor; prevents direct creation of object.
     */
    private function __construct()
    {
    }

    /**
     * Returns the singleton.
     *
     * @return the singleton
     */
    public static function singleton()
    {
        if (!isset(self::$_instance)) {
            $c = __CLASS__;
            self::$_instance = new $c;
        }

        return self::$_instance;
    }

    /**
     * Scales each row.
     *
     * @param string $spatial spatial data of a row
     *
     * @return array containing the min, max values for x and y cordinates
     */
    public function scaleRow($spatial)
    {
        $min_max = array();

        // Trim to remove leading 'MULTIPOLYGON(((' and trailing ')))'
        $multipolygon = substr($spatial, 15, (strlen($spatial) - 18));
        // Seperate each polygon
        $polygons = explode(")),((", $multipolygon);

        foreach ($polygons as $polygon) {
            // If the polygon doesnt have an inner polygon
            if (strpos($polygon, "),(") === false) {
                $min_max = $this->setMinMax($polygon, $min_max);
            } else {
                // Seperate outer and inner polygons
                $parts = explode("),(", $polygon);
                $outer = $parts[0];
                $inner = array_slice($parts, 1);

                $min_max = $this->setMinMax($outer, $min_max);

                foreach ($inner as $inner_poly) {
                    $min_max = $this->setMinMax($inner_poly, $min_max);
                }
            }
        }

        return $min_max;
    }

    /**
     * Adds to the PNG image object, the data related to a row in the GIS dataset.
     *
     * @param string $spatial    GIS MULTIPOLYGON object
     * @param string $label      Label for the GIS MULTIPOLYGON object
     * @param string $fill_color Color for the GIS MULTIPOLYGON object
     * @param array  $scale_data Array containing data related to scaling
     * @param image  $image      Image object
     *
     * @return the code related to a row in the GIS dataset
     */
    public function prepareRowAsPng($spatial, $label, $fill_color, $scale_data, $image)
    {
        // allocate colors
        $r = hexdec(substr($fill_color, 1, 2));
        $g = hexdec(substr($fill_color, 3, 2));
        $b = hexdec(substr($fill_color, 4, 2));
        $color = imagecolorallocate($image, $r, $g, $b);

        // Trim to remove leading 'MULTIPOLYGON(((' and trailing ')))'
        $multipolygon = substr($spatial, 15, (strlen($spatial) - 18));
        // Seperate each polygon
        $polygons = explode(")),((", $multipolygon);

        foreach ($polygons as $polygon) {
            // If the polygon doesnt have an inner polygon
            if (strpos($polygon, "),(") === false) {
                $points_arr = $this->extractPoints($polygon, $scale_data, true);
            } else {
                // Seperate outer and inner polygons
                $parts = explode("),(", $polygon);
                $outer = $parts[0];
                $inner = array_slice($parts, 1);

                $points_arr = $this->extractPoints($outer, $scale_data, true);

                foreach ($inner as $inner_poly) {
                    $points_arr = array_merge(
                        $points_arr, $this->extractPoints($inner_poly, $scale_data, true)
                    );
                }
            }
            // draw polygon
            imagefilledpolygon($image, $points_arr, sizeof($points_arr) / 2, $color);
        }
        return $image;
    }

    /**
     * Prepares and returns the code related to a row in the GIS dataset as SVG.
     *
     * @param string $spatial    GIS MULTIPOLYGON object
     * @param string $label      Label for the GIS MULTIPOLYGON object
     * @param string $fill_color Color for the GIS MULTIPOLYGON object
     * @param array  $scale_data Array containing data related to scaling
     *
     * @return the code related to a row in the GIS dataset
     */
    public function prepareRowAsSvg($spatial, $label, $fill_color, $scale_data)
    {
        $polygon_options = array(
            'name'        => $label,
            'class'       => 'multipolygon',
            'stroke'      => 'black',
            'stroke-width'=> 0.5,
            'fill'        => $fill_color,
            'fill-rule'   => 'evenodd',
            'fill-opacity'=> 0.8,
        );

        $row = '';

        // Trim to remove leading 'MULTIPOLYGON(((' and trailing ')))'
        $multipolygon = substr($spatial, 15, (strlen($spatial) - 18));
        // Seperate each polygon
        $polygons = explode(")),((", $multipolygon);

        foreach ($polygons as $polygon) {
            $row .= '<path d="';

            // If the polygon doesnt have an inner polygon
            if (strpos($polygon, "),(") === false) {
                $row .= $this->_drawPath($polygon, $scale_data);
            } else {
                // Seperate outer and inner polygons
                $parts = explode("),(", $polygon);
                $outer = $parts[0];
                $inner = array_slice($parts, 1);

                $row .= $this->_drawPath($outer, $scale_data);

                foreach ($inner as $inner_poly) {
                    $row .= $this->_drawPath($inner_poly, $scale_data);
                }
            }
            $polygon_options['id'] = $label . rand();
            $row .= '"';
            foreach ($polygon_options as $option => $val) {
                $row .= ' ' . $option . '="' . trim($val) . '"';
            }
            $row .= '/>';
        }

        return $row;
    }

    /**
     * Draws a ring of the polygon using SVG path element.
     *
     * @param string $polygon    The ring
     * @param array  $scale_data Array containing data related to scaling
     *
     * @return the code to draw the ring
     */
    private function _drawPath($polygon, $scale_data)
    {
        $points_arr = $this->extractPoints($polygon, $scale_data);

        $row = ' M ' . $points_arr[0][0] . ', ' . $points_arr[0][1];
        $other_points = array_slice($points_arr, 1, count($points_arr) - 2);
        foreach ($other_points as $point) {
            $row .= ' L ' . $point[0] . ', ' . $point[1];
        }
        $row .= ' Z ';

        return $row;
    }
}
?>
