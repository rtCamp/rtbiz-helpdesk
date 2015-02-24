<?php
/**
 * Created by PhpStorm.
 * User: dips
 * Date: 24/2/15
 * Time: 4:13 PM
 */

if ( ! class_exists( 'Rt_HD_Auto_Respond' ) ) {
    /**
     * Class Rt_HD_Auto_Respond
     */
    class Rt_HD_Auto_Respond {

        /**
         * Constructor
         */
        public function __construct() {
            $this->hooks();
        }

        /**
         * hook function
         *
         * @since rt-Helpdesk 1.1
         */
        function hooks() {
            add_action( 'rt_hd_auto_respond', array( $this, 'auto_respond' ), 10, 2 );
            //add_action( 'wp', array( $this, 'test' ), 10, 2 );
        }

        function test(){
            $date = current_time( 'mysql', 1 );
            $d             = new DateTime( $date );
            $timeStamp     = $d->getTimestamp();
            $post_date     = gmdate( 'Y-m-d H:i:s', ( intval( $timeStamp ) + ( get_option( 'gmt_offset' ) * 3600 ) ) );
            do_action( 'rt_hd_auto_respond', 108, $post_date );
        }

        /**
         * auto respond functionality : add auto followup when ticket or followup created
         *
         * @param $post_id
         * @param $post_date
         */
        function  auto_respond( $post_id, $post_date ){
            $redux = rthd_get_redux_settings();
            $isEnableAutoRespond = ( isset( $redux['rthd_enable_auto_respond']) && $redux['rthd_enable_auto_respond'] == 1 ) ;
            $isDayShift = ( isset( $redux['rthd_enable_auto_respond_mode']) && $redux['rthd_enable_auto_respond_mode'] == 1 ) ;
            $weekdays = array( 'Monday', 'Tuesday', 'Wednesday', 'Thursday', 'Friday', 'Saturday', 'Sunday' );
            if ( $isEnableAutoRespond ){
                $d             = new DateTime( $post_date );
                $timeStamp     = $d->getTimestamp();
                $day = date( 'N', $timeStamp ) - 1; // date returns 1 for monday & 7 for  sunday
                $hour = date( 'H', $timeStamp );
                $hour =
                $nextday = -1;
                if ( $isDayShift ){
                    $shifttime = array();
                    $shifttime['start'] = isset( $redux['rthd_dayshift_time_start']) ? $redux['rthd_dayshift_time_start'] : array( 0 => -1 , 1 => -1, 2 => -1, 3 => -1, 4 => -1, 5 => -1, 6 => -1 );
                    $shifttime['end'] = isset( $redux['rthd_dayshift_time_end']) ? $redux['rthd_dayshift_time_end'] : array( 0 => -1 , 1 => -1, 2 => -1, 3 => -1, 4 => -1, 5 => -1, 6 => -1 );
                    if ( ! empty( $shifttime['start'] ) ){

                        // if [ time not empty and off time ] or [ time is empty ]
                        if ( ( -1 != $shifttime['start'][ $day ] && -1 != $shifttime['end'][ $day ] && ( $hour < $shifttime['start'][ $day ] || $hour >= $shifttime['end'][ $day ] ) ) || ( -1 == $shifttime['start'][ $day ] && -1 == $shifttime['end'][ $day ] ) ){
                            print_r( 'followup added' );

                            // Get next Working hours
                            $nextday = $this->next_day( $day + 1, $shifttime, $isDayShift );
                            //get next staring time
                            $NextStatingTime = $shifttime['start'][ $nextday ];
                            // check nextday is same day or not
                            if ( ( $nextday == $day && $NextStatingTime < $hour ) || $nextday != $day ){
                                $nextday = $weekdays[ $nextday ] . ' after ';
                            } else {
                                $nextday = 'Today after ';
                            }
                            $nextday .= ( $NextStatingTime > 12 ) ? ( $NextStatingTime- 12 ) . ' PM' : $NextStatingTime . ' AM';
                            var_dump( $nextday );

                        }
                    }
                } else {
                    $shifttime = array();
                    $shifttime['am_start'] = isset( $redux['rthd_daynight_am_time_start']) ? $redux['rthd_daynight_am_time_start'] : array( 0 => -1 , 1 => -1, 2 => -1, 3 => -1, 4 => -1, 5 => -1, 6 => -1 );
                    $shifttime['am_end'] = isset( $redux['rthd_daynight_am_time_end']) ? $redux['rthd_daynight_am_time_end'] : array( 0 => -1 , 1 => -1, 2 => -1, 3 => -1, 4 => -1, 5 => -1, 6 => -1 );
                    $shifttime['pm_start'] = isset( $redux['rthd_daynight_pm_time_start']) ? $redux['rthd_daynight_pm_time_start'] : array( 0 => -1 , 1 => -1, 2 => -1, 3 => -1, 4 => -1, 5 => -1, 6 => -1 );
                    $shifttime['pm_end'] = isset( $redux['rthd_daynight_pm_time_end']) ? $redux['rthd_daynight_pm_time_end'] : array( 0 => -1 , 1 => -1, 2 => -1, 3 => -1, 4 => -1, 5 => -1, 6 => -1 );
                    if ( ! empty( $shifttime ) ){

                        // if [ time not empty and off time ] or [ time is empty ]
                        if ( ( ( -1 != $shifttime['am_start'][ $day ] && -1 != $shifttime['am_end'][ $day ] && -1 != $shifttime['pm_start'][ $day ] && -1 != $shifttime['pm_end'][ $day ] ) && ( ( $hour < $shifttime['am_start'][ $day ] || $hour >= $shifttime['am_end'][ $day ] ) && ( $hour < $shifttime['pm_start'][ $day ] || $hour >= $shifttime['pm_end'][ $day ] ) ) ) || ( -1 == $shifttime['am_start'][ $day ] && -1 == $shifttime['am_end'][ $day ] && -1 == $shifttime['pm_start'][ $day ] && -1 == $shifttime['pm_end'][ $day ] ) ){
                            print_r( 'followup added' );

                            // Get next Working hours
                            $nextday = ( $hour <= 12 ) ? $day : ( $day + 1 ) ;
                            $nextday = $this->next_day( $nextday, $shifttime, $isDayShift );
                            //get next staring time
                            if ( $hour >= 12 ) {
                                $NextStatingTime = $shifttime['am_start'][ $nextday ];
                            }else{
                                $NextStatingTime = $shifttime['pm_start'][ $nextday ];
                            }
                            // check nextday is same day or not
                            if ( ( $nextday == $day && $NextStatingTime < $hour ) || $nextday != $day ){
                                $nextday = $weekdays[ $nextday ] . ' after ';
                            } else {
                                $nextday = 'Today after ';
                            }
                            $nextday .= ( $NextStatingTime > 12 ) ? ( $NextStatingTime- 12 ) . ' PM' : $NextStatingTime . ' AM';

                            var_dump( $nextday );
                        }
                    }
                }
            }
        }

        /**
         * get next day which is working day
         *
         * @param $day
         * @param $shifttime
         * @param $isDayShift
         * @return int
         */
        function next_day( $day, $shifttime, $isDayShift ){
            if ( $day < 0 || $day > 7 ){
                return $day;
            }
            if ( $day > 6 ){
                $day = 0;
            }

            // next day office time set or not
            if ( $isDayShift ){
                if ( ! empty( $shifttime ) ){
                    if ( empty( $shifttime['start'][ $day ] ) || empty( $shifttime['end'][ $day ] ) ){
                        return $this->next_day( $day + 1, $shifttime, $isDayShift  );
                    }
                }
            }else{
                if ( ! empty( $shifttime ) ){
                    if ( empty( $shifttime['am_start'][ $day ] ) && empty( $shifttime['am_end'][ $day ] ) && empty( $shifttime['pm_start'][ $day ] ) && empty( $shifttime['pm_end'][ $day ] ) ) {
                        return $this->next_day( $day + 1, $shifttime, $isDayShift  );
                    }
                }
            }

            return $day;
        }
    }
}
