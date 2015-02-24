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
         */
        function  auto_respond( $post_id, $post_date ){
            $redux = rthd_get_redux_settings();
            $isEnableAutoRespond = ( isset( $redux['rthd_enable_auto_respond']) && $redux['rthd_enable_auto_respond'] == 1 ) ;
            $isDayShift = ( isset( $redux['rthd_enable_auto_respond_mode']) && $redux['rthd_enable_auto_respond_mode'] == 1 ) ;

            if ( $isEnableAutoRespond ){
                $d             = new DateTime( $post_date );
                $timeStamp     = $d->getTimestamp();
                $day = date( 'N', $timeStamp ) - 1; // date returns 1 for monday & 7 for  sunday
                $hour = date( 'H', $timeStamp );
                $nextday = -1;
                if ( $isDayShift ){
                    $shifttime = array();
                    $shifttime['start'] = isset( $redux['rthd_dayshift_time_start']) ? $redux['rthd_dayshift_time_start'] : array( 0 => '' , 1 => '1', 2 => '', 3 => '', 4 => '', 5 => '', 6 => '' );
                    $shifttime['end'] = isset( $redux['rthd_dayshift_time_end']) ? $redux['rthd_dayshift_time_end'] : array( 0 => '' , 1 => '5', 2 => '', 3 => '', 4 => '', 5 => '', 6 => '' );
                    if ( ! empty( $shifttime ) ){
                        if ( ! empty( $shifttime['start'][ $day ] ) && ! empty( $shifttime['end'][ $day ] ) ){
                            if ( $hour < $shifttime['start'][ $day ] || $hour >= $shifttime['end'][ $day ] ){
                                print_r( 'followup added' );
                                $nextday = $this->next_day( $day + 1, $shifttime, $isDayShift );
                                var_dump( $nextday );
                            }
                        }
                    }
                } else {
                    $shifttime = array();
                    $shifttime['am_start'] = isset( $redux['rthd_daynight_am_time_start']) ? $redux['rthd_daynight_am_time_start'] : array( 0 => '' , 1 => '', 2 => '', 3 => '', 4 => '', 5 => '', 6 => '' );
                    $shifttime['am_end'] = isset( $redux['rthd_daynight_am_time_end']) ? $redux['rthd_daynight_am_time_end'] : array( 0 => '' , 1 => '', 2 => '', 3 => '', 4 => '', 5 => '', 6 => '' );
                    $shifttime['pm_start'] = isset( $redux['rthd_daynight_pm_time_start']) ? $redux['rthd_daynight_pm_time_start'] : array( 0 => '' , 1 => '', 2 => '', 3 => '', 4 => '', 5 => '', 6 => '' );
                    $shifttime['pm_end'] = isset( $redux['rthd_daynight_pm_time_end']) ? $redux['rthd_daynight_pm_time_end'] : array( 0 => '' , 1 => '', 2 => '', 3 => '', 4 => '', 5 => '', 6 => '' );
                    if ( ! empty( $shifttime ) ){
                        if ( ! empty( $shifttime['am_start'][ $day ] ) && ! empty( $shifttime['am_end'][ $day ] ) && ! empty( $shifttime['pm_start'][ $day ] ) && ! empty( $shifttime['pm_end'][ $day ] ) ){
                            if ( ( $hour < $shifttime['am_start'][ $day ] || $hour >= $shifttime['am_end'][ $day ] ) && ( $hour < $shifttime['pm_start'][ $day ] || $hour >= $shifttime['pm_end'][ $day ] ) ){
                                print_r( 'followup added' );
                                $nextday = ( $hour < 12 ) ? $day : ( $day + 1 ) ;
                                $nextday = $this->next_day( $nextday );
                            }
                        }
                    }
                }
            }
        }

        function next_day( $day, $shifttime, $isDayShift ){
            if ( $day < 0 || $day > 7 ){
                return $day;
            }
            if ( $day > 6 ){
                $day = 0;
            }
            if ( $isDayShift ){
                if ( ! empty( $shifttime ) ){
                    if ( empty( $shifttime['start'][ $day ] ) || empty( $shifttime['end'][ $day ] ) ){
                        return $this->next_day( $day + 1, $shifttime, $isDayShift  );
                    }
                }
            }
            $weekdays = array( 'Monday', 'Tuesday', 'Wednesday', 'Thursday', 'Friday', 'Saturday', 'Sunday' );
            return $weekdays[ $day ];
        }

    }
}
