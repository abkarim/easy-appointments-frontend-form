<?php
/*
 * Plugin Name:       Easy appointments frontend form
 * Plugin URI:        https://github.com/abkarim/easy-appointments-frontend-form
 * Description:       view appointments in frontend by using shortcode "[Easy_Appointments_Frontend_FormShortcode]"
 * Version:           0.1.2
 * Requires at least: 5.2
 * Requires PHP:      7.4
 * Author:            Karim
 * Author URI:        https://github.com/abkarim
 * License:           GPL-3.0 license
 * License URI:       https://www.gnu.org/licenses/gpl-3.0.en.html
 * Update URI:
 * Text Domain:       easy-appointments-frontend-form
 * Domain Path:       /languages
 */

/**
 * !Prevent direct access
 */
if (!defined("ABSPATH")) {
    exit();
}

if (!class_exists("Easy_Appointments_Frontend_Form")) {
    class Easy_Appointments_Frontend_Form
    {
        public $short_code = "Easy_Appointments_Frontend_FormShortcode";

        /**
         * Constructor
         */
        public function __construct()
        {
            /**
             * Load plugin
             */
            add_action("plugins_loaded", [$this, "init"]);
        }

        /**
         * Initialize plugin
         *
         * Called by plugins_loaded hook
         *
         * @access public
         * @since 0.1.0
         */
        public function init()
        {
            add_action( 'init', [$this, 'register_shortcode']);
        }
        
        /**
         * Register shortcode
         * 
         * @since 0.1.0
         * @access public
         */
        public function register_shortcode() {
            add_shortcode( $this->short_code, [$this, 'render_forms_html'] );
        }

        /**
         * Get user data 
         * 
         * @param int app_id
         * @return array valid_data
         * @since 0.1.1
         * @access private
         */
        private function get_user_data($app_id) {
            global  $wpdb;
            $fields_table_name = $wpdb->prefix . 'ea_fields';

            $query = "SELECT field_id, value FROM $fields_table_name WHERE app_id = $app_id ORDER BY field_id ASC";

            $data = $wpdb->get_results($query, ARRAY_A );

            if(count($data) !== 0) {
                return $data;
            }
            
            return [];
        }


        /**
         * Get appointments 
         * 
         * @param string date
         * @return mixed array||null appointments object
         * @access private
         * @since 0.1.0
         */
        private function get_appointments($date) {
            global $wpdb;
            $appointments_table_name = $wpdb->prefix . 'ea_appointments';
            $locations_table_name = $wpdb->prefix . 'ea_locations';
            $services_table_name = $wpdb->prefix . 'ea_services';
            $staffs_table_name = $wpdb->prefix . 'ea_staff';

            $query = "
            SELECT 
                $appointments_table_name.id, 
                $appointments_table_name.date, 
                $appointments_table_name.start, 
                $appointments_table_name.end, 
                $appointments_table_name.status,
                $locations_table_name.name as location_name,
                $locations_table_name.address as location_address,
                $locations_table_name.location as location_location,
                $services_table_name.name as service_name,
                $staffs_table_name.name as staff_name
            FROM $appointments_table_name 
            LEFT JOIN $locations_table_name ON $appointments_table_name.location = $locations_table_name.id
            LEFT JOIN $services_table_name ON $appointments_table_name.service = $services_table_name.id
            LEFT JOIN $staffs_table_name ON $appointments_table_name.worker = $staffs_table_name.id
            WHERE $appointments_table_name.date = '$date'";

            // Execute query
            $data = $wpdb->get_results($query, ARRAY_A );

            if (count($data) !== 0) {
                return $data;
            }

            return null;
        }

        /**
         * Render forms HTML
         * 
         * called from add_shortcode hook in $this->register_shortcode()
         * 
         * @since 0.1.0
         * @return string
         * @access public
         */
        public function render_forms_html($atts=[]) {

            $selectedFields = isset($atts["fields"]) ? $atts["fields"] : null;
            $fields_with_name = [];

            if($selectedFields) {
                /**
                 * It should be in json format
                 */
                $decodedFields = json_decode($selectedFields, true);
                if($decodedFields){
                    $fields_with_name = $decodedFields;
                }
            }

         
            /**
             * Container
             */
            $html = "<section>";

            /**
             * Results
             */
            $results = "";
            $date = "";
            $tableHeader = "";
            $tableBody = "";

            /**
             * Get form data
             */
            if(isset($_POST['date'])) {
                $date = $_POST['date'];

                /**
                 * Valid date found
                 * 0000-00-00 - format
                 */
                if(preg_match("/^\d{4}-\d{2}-\d{2}/", $date)) {
                    $data = $this->get_appointments($date);

                    if(is_null($data)) {
                        $results .= "Sorry, no appointments found";
                    }else {
                   
                        /**
                         * Table body start
                         */
                        $tableBody = "<tbody>";

                        /**
                         * Headers fields array
                         * fields id
                         */
                        $headerFieldsArray = [];

                        /**
                         * Table row with results
                         */
                        $index = 1;
                        foreach($data as $appointment ) {
                            $backgroundColor = $index % 2 == 0 ? '#E8E9EB' : 'white';
                            $fieldsArray = $this->get_user_data($appointment['id']);
                            $fields_rows = "";

                            /**
                             * Prepare fields row
                             * 
                             * add every 
                             */
                            foreach($fieldsArray as $field) {
                                $field_id = intval($field['field_id']);

                                /**
                                 * When ge got fields name
                                 */
                                if(count($fields_with_name) !== 0) {
                                    if(isset($fields_with_name[$field_id])) {
                                        if(!in_array($field_id, $headerFieldsArray)) {
                                            array_push($headerFieldsArray, $field_id);
                                        }
                                        
                                        $value = $field['value'];
                                        $fields_rows .= "<td style='border: 1px solid black; border-collapse: collapse;'>$value</td>";                                
                                    }
                                    
                                }else {   
                                    if(!in_array($field_id, $headerFieldsArray)) {
                                        array_push($headerFieldsArray, $field_id);
                                    }
                                    
                                    $value = $field['value'];
                                    $fields_rows .= "<td style='border: 1px solid black; border-collapse: collapse;'>$value</td>";                                
                                }
                            }


                            /**
                             * Prepare rows
                             */
                            $tableBody .= "<tr style='background-color: $backgroundColor;'>
                                            <td style='border: 1px solid black; border-collapse: collapse;'>$index</td>
                                            <td style='border: 1px solid black; border-collapse: collapse;'>". $appointment['date'] ."</td>
                                            <td style='border: 1px solid black; border-collapse: collapse;'>". $appointment['start'] . " - ".$appointment['end'] ."</td>".
                                            $fields_rows
                                            ."<td style='border: 1px solid black; border-collapse: collapse;'>".$appointment['service_name']."</td>
                                            <td style='border: 1px solid black; border-collapse: collapse;'>".$appointment['location_address']. "</td>
                                            <td style='border: 1px solid black; border-collapse: collapse;'>".$appointment['staff_name']. "</td>
                                            <td style='border: 1px solid black; border-collapse: collapse;'>".$appointment['status'] ."</td>
                                        </tr>";
                            $index += 1;
                        }

                        /**
                         * Table body end
                         */
                        $tableBody .= "</tbody>"; 

                        $headerFieldsString = "";

                        foreach($headerFieldsArray as $field_id) {
                            $name = $field_id;

                            if(count($fields_with_name) !==0 ) {
                                $name = $fields_with_name[$name];
                            } 

                            $headerFieldsString .= "<th style='border: 1px solid black; border-collapse: collapse;'>$name</th>";
                        }

                        /**
                         * Table heading
                         */
                        $tableHeader = "<thead>
                                        <th style='border: 1px solid black; border-collapse: collapse;'>#</th>
                                        <th style='border: 1px solid black; border-collapse: collapse;'>Appointment Date</th>
                                        <th style='border: 1px solid black; border-collapse: collapse;'>Appointment Time</th>
                                        $headerFieldsString
                                        <th style='border: 1px solid black; border-collapse: collapse;'>Service Name</th>
                                        <th style='border: 1px solid black; border-collapse: collapse;'>Location</th>
                                        <th style='border: 1px solid black; border-collapse: collapse;'>Appointment To</th>
                                        <th style='border: 1px solid black; border-collapse: collapse;'>Appointment Status</th>
                                    </thead>";

                    }

                }

            }

            /**
             * Form html
             */
            $html = "<div>
                        <form method='POST'>
                            <div>
                                <label>Please select a date</label>
                                <input name='date' value='$date' type='date' required />
                            </div>
                            <div>
                                <button type='submit' name='submit'>Submit</button>
                            </div>
                        </form>
                    </div>";
            /**
             * Results table
             */
            $html .= "<div>
                        <h3>$date</h3>
                        <table style='border: 1px solid black; border-collapse: collapse;'>
                            $tableHeader
                            $tableBody
                        </table>
                    </div>";                
            $html .= "</section>";

            return $html;
        }

      
    }

    new Easy_Appointments_Frontend_Form();
}