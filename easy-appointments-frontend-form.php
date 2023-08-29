<?php
/*
 * Plugin Name:       Easy appointments frontend form
 * Plugin URI:        https://github.com/abkarim/easy-appointments-frontend-form
 * Description:       view appointments in frontend by using shortcode "[Easy_Appointments_Frontend_FormShortcode]"
 * Version:           0.1.0
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
                $appointments_table_name.name, 
                $appointments_table_name.email, 
                $appointments_table_name.phone, 
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
            $data = $wpdb->get_results($query, OBJECT_K );

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
        public function render_forms_html() {

            /**
             * Container
             */
            $html = "<section>";

            /**
             * Results
             */
            $results = "";
            $date = "";

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
                         * Table heading
                         */
                        $results .= "<thead>
                                        <th>#</th>
                                        <th>Appointment Date</th>
                                        <th>Appointment Time</th>
                                        <th>Name</th>
                                        <th>Email</th>
                                        <th>Phone</th>
                                        <th>Service Name</th>
                                        <th>Location</th>
                                        <th>Appointment To</th>
                                        <th>Appointment Status</th>
                                    </thead>";

                        /**
                         * Table body start
                         */
                        $results .= "<tbody>";

                        /**
                         * Table row with results
                         */
                        foreach($data as $index => $appointment ) {
                            $results .= "<tr>
                                            <td>$index</td>
                                            <td>$appointment->date</td>
                                            <td>$appointment->start - $appointment->end</td>
                                            <td>$appointment->name</td>
                                            <td>$appointment->email</td>
                                            <td>$appointment->phone</td>
                                            <td>$appointment->service_name</td>
                                            <td>$appointment->location_address</td>
                                            <td>$appointment->staff_name</td>
                                            <td>$appointment->status</td>
                                        </tr>";
                        }

                        /**
                         * Table body end
                         */
                        $results .= "</tbody>"; 
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
                        <table>
                            $results
                        </table>
                    </div>";                
            $html .= "</section>";

            return $html;
        }

      
    }

    new Easy_Appointments_Frontend_Form();
}