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
        }

        /**
         * auto respond functionality : add auto followup when ticket or followup created
         *
         * @param $comment_post_ID
         * @param $post_date
         */
        function  auto_respond( $comment_post_ID, $post_date ){
            global $rt_hd_import_operation;
            $redux = rthd_get_redux_settings();
            $isEnableAutoRespond = ( isset( $redux['rthd_enable_auto_respond']) && $redux['rthd_enable_auto_respond'] == 1 ) ;
            $isDayShift = ( isset( $redux['rthd_enable_auto_respond_mode']) && $redux['rthd_enable_auto_respond_mode'] == 1 ) ;
            $weekdays = array( 'Monday', 'Tuesday', 'Wednesday', 'Thursday', 'Friday', 'Saturday', 'Sunday' );
            $placeholder_list = array();

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
                            $placeholder_list['NextStartingHour'] = $nextday;

                            $userid = get_post_field( 'post_author', $comment_post_ID ); //post author
                            $comment_content = rthd_get_auto_respond_message();
                            foreach ( $placeholder_list as $key => $value ){
                                $comment_content = str_replace( '{' . $key . '}', $value, $comment_content );
                            }
                            $comment_author = 'Helpdesk Bot';
                            $comment_author_email = '';
                            $commenttime = current_time( 'mysql', 1 );

                            $rt_hd_import_operation->insert_post_comment( $comment_post_ID, $userid, $comment_content, $comment_author, $comment_author_email, $commenttime, array(), array(), array(), '', '', '', array(), '', '40', 0, true );

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
                            $placeholder_list['NextStartingHour'] = $nextday;

                            $userid = get_post_field( 'post_author', $comment_post_ID ); //post author
                            $comment_content = rthd_get_auto_respond_message();
                            foreach ( $placeholder_list as $key => $value ){
                                $comment_content = str_replace( '{' . $key . '}', $value, $comment_content );
                            }
                            $comment_author = 'Helpdesk Bot';
                            $comment_author_email = '';
                            $commenttime = current_time( 'mysql', 1 );

                            $rt_hd_import_operation->insert_post_comment( $comment_post_ID, $userid, $comment_content, $comment_author, $comment_author_email, $commenttime, array(), array(), array(), '', '', '', array(), '', '40', 0, true );
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

        /**
         * UI for dayshift setting
         */
        function setting_dayshift_ui(){
            $redux = rthd_get_redux_settings();
            $shifttime = array();
            $shifttime['start'] = isset( $redux['rthd_dayshift_time_start']) ? $redux['rthd_dayshift_time_start'] : array( 0 => -1 , 1 => -1, 2 => -1, 3 => -1, 4 => -1, 5 => -1, 6 => -1 );
            $shifttime['end'] = isset( $redux['rthd_dayshift_time_end']) ? $redux['rthd_dayshift_time_end'] : array( 0 => -1 , 1 => -1, 2 => -1, 3 => -1, 4 => -1, 5 => -1, 6 => -1 );
            $weekdays = array( 'Monday', 'Tuesday', 'Wednesday', 'Thursday', 'Friday', 'Saturday', 'Sunday' ); ?>
            <table id="rthd-respond">
                <tbody>
                <?php foreach ( $weekdays as $key => $weekday ) : ?>
                    <tr class="rthd-dayshift-info">
                        <td>
                            <label><?php echo $weekday; ?></label>
                        </td>
                        <td>
                            <select class="rthd-dayshift-time-start" name="redux_helpdesk_settings[rthd_dayshift_time_start][<?php echo $key; ?>]">
                                <option value="-1">Select Time</option>
                                <?php for( $i = 0; $i < 24; $i++ ) {
                                    $selected = $shifttime['start'][ $key ] == $i ? 'selected' : '';
                                    echo '<option value="'.$i.'" ' . $selected . '>' . date( "H:i", strtotime( $i . ":00" ) ) . '</option>'. "\n" ;
                                } ?>
                            </select>
                            &nbsp;&nbsp;To&nbsp;&nbsp;
                            <select class="rthd-dayshift-time-end" name="redux_helpdesk_settings[rthd_dayshift_time_end][<?php echo $key; ?>]" data-value="<?php echo $shifttime['end'][ $key ]; ?>">
                                <option value="-1">Select Time</option>
                                <?php for( $i = 0; $i < 24; $i++ ) {
                                    $selected = $shifttime['end'][ $key ] == $i ? 'selected' : '';
                                    echo '<option value="'.$i.'" ' . $selected . '>' . date( "H:i", strtotime( $i . ":59" ) ) . '</option>'. "\n" ;
                                } ?>
                            </select>
                        </td>
                    </tr>
                    <tr class="rthd-dayshift-error">
                        <td>&nbsp</td>
                        <td class="error"></td>
                    </tr>
                <?php endforeach; ?>
                </tbody>
            </table>

            <script type="text/javascript">
                jQuery( document ).ready( function() {

                    function time_valiation( $tr_parent ) {
                        var starting_val = $tr_parent.find( '.rthd-dayshift-time-start' ).val();
                        var ending_val = $tr_parent.find( '.rthd-dayshift-time-end' ).val();

                        if( starting_val != -1 && ending_val != -1 && parseInt( ending_val ) <= parseInt( starting_val ) ) {
                            jQuery( $tr_parent ).next('.rthd-dayshift-error').show().find( '.error' ).html( 'Please select `Ending` time greater than `Starting` time or `Starting time less than `Ending` time.' );
                            return false;
                        }
                        else {
                            jQuery( $tr_parent ).next('.rthd-dayshift-error').hide();
                            return true;
                        }
                    }
                    jQuery( '.rthd-dayshift-time-end' ).change( function() {
                        time_valiation( jQuery( this ).parent().parent() );
                    });
                    jQuery( '.rthd-dayshift-time-start' ).change( function() {
                        time_valiation( jQuery( this ).parent().parent() );
                    });
                    jQuery( '#redux-form-wrapper' ).submit( function() {
                        var flag = true;

                        for( var i = 0; i < 7; i++ ) {
                            var starting_val = jQuery( '.rthd-dayshift-time-start' ).eq( i ).val();
                            var ending_val = jQuery( '.rthd-dayshift-time-end' ).eq( i ).val();

                            if( starting_val != -1 && ending_val != -1 ) {
                                if( jQuery( '.rthd-time-end' ).eq( i ).change() == false ) {
                                    flag = false;
                                }
                            }
                            else if( starting_val != -1 && ending_val == -1 ) {
                                jQuery( '.rthd-dayshift-time-end' ).eq( i ).parent().parent().next('.rthd-dayshift-error').show().find( '.error' ).html( 'Please select `Ending` time.' );
                                flag = false;
                            }
                            else if( starting_val == -1 && ending_val != -1 ) {
                                jQuery( '.rthd-dayshift-time-start' ).eq( i ).parent().parent().next('.rthd-dayshift-error').show().find( '.error' ).html( 'Please select `Starting` time.' );
                                flag = false;
                            }
                        }

                        if( ! flag ) {
                            return false;
                        }
                        else {
                            return true;
                        }
                    });
                });
            </script>
        <?php
        }

        /**
         * UI for daynightshift setting
         */
        function setting_daynightshift_ui(){
            $redux = rthd_get_redux_settings();
            $shifttime = array();
            $shifttime['am_start'] = isset( $redux['rthd_daynight_am_time_start']) ? $redux['rthd_daynight_am_time_start'] : array( 0 => -1 , 1 => -1, 2 => -1, 3 => -1, 4 => -1, 5 => -1, 6 => -1 );
            $shifttime['am_end'] = isset( $redux['rthd_daynight_am_time_end']) ? $redux['rthd_daynight_am_time_end'] : array( 0 => -1 , 1 => -1, 2 => -1, 3 => -1, 4 => -1, 5 => -1, 6 => -1 );
            $shifttime['pm_start'] = isset( $redux['rthd_daynight_pm_time_start']) ? $redux['rthd_daynight_pm_time_start'] : array( 0 => -1 , 1 => -1, 2 => -1, 3 => -1, 4 => -1, 5 => -1, 6 => -1 );
            $shifttime['pm_end'] = isset( $redux['rthd_daynight_pm_time_end']) ? $redux['rthd_daynight_pm_time_end'] : array( 0 => -1 , 1 => -1, 2 => -1, 3 => -1, 4 => -1, 5 => -1, 6 => -1 );

            $weekdays = array( 'Monday', 'Tuesday', 'Wednesday', 'Thursday', 'Friday', 'Saturday', 'Sunday' ); ?>
            <table id="rthd-respond">
                <tbody>
                <tr>
                    <th>&nbsp;</th>
                    <th>AM</th>
                    <th>PM</th>
                </tr>
                <?php foreach ( $weekdays as $key => $weekday ) : ?>
                    <tr class="rthd-daynightshift-info">
                        <td>
                            <label><?php echo $weekday; ?></label>
                        </td>
                        <td>
                            <select class="rthd-daynigt-am-time-start" name="redux_helpdesk_settings[rthd_daynight_am_time_start][<?php echo $key; ?>]">
                                <option value="-1">Select Time</option>
                                <?php for( $i = 0; $i <= 11; $i++ ) {
                                    $selected = $shifttime['am_start'][ $key ] == $i ? 'selected' : '';
                                    echo '<option value="'.$i.'" ' . $selected . '>' . date( "H:i", strtotime( $i . ":00" ) ) . '</option>'. "\n" ;
                                } ?>
                            </select>
                            &nbsp;To&nbsp;
                            <select class="rthd-daynigt-am-time-end" name="redux_helpdesk_settings[rthd_daynight_am_time_end][<?php echo $key; ?>]">
                                <option value="-1">Select Time</option>';
                                <?php for( $i = 0; $i <= 11; $i++ ) {
                                    $selected = $shifttime['am_end'][ $key ] == $i ? 'selected' : '';
                                    echo '<option value="'.$i.'" ' . $selected . '>' . date( "H:i", strtotime( $i . ":59" ) ) . '</option>'. "\n" ;
                                } ?>
                            </select>
                        </td>
                        <td>
                            <select class="rthd-daynigt-pm-time-start" name="redux_helpdesk_settings[rthd_daynight_pm_time_start][<?php echo $key; ?>]">
                                <option value="-1">Select Time</option>
                                <?php for( $i = 12; $i <= 23; $i++ ) {
                                    $selected = $shifttime['pm_start'][ $key ] == $i ? 'selected' : '';
                                    echo '<option value="'.$i.'" ' . $selected . '>' . date( "H:i", strtotime( $i . ":00" ) ) . '</option>'. "\n" ;
                                } ?>
                            </select>
                            &nbsp;To&nbsp;
                            <select class="rthd-daynigt-pm-time-end" name="redux_helpdesk_settings[rthd_daynight_pm_time_end][<?php echo $key; ?>]">
                                <option value="-1">Select Time</option>';
                                <?php for( $i = 12; $i <= 23; $i++ ) {
                                    $selected = $shifttime['pm_end'][ $key ] == $i ? 'selected' : '';
                                    echo '<option value="'.$i.'" ' . $selected . '>' . date( "H:i", strtotime( $i . ":59" ) ) . '</option>'. "\n" ;
                                } ?>
                            </select>
                        </td>
                    </tr>
                    <tr class="rthd-daynightshift-error">
                        <td>&nbsp</td>
                        <td class="am-time-error"></td>
                        <td class="pm-time-error"></td>
                    </tr>
                <?php endforeach; ?>
                </tbody>
            </table>

            <script type="text/javascript">
                /*jQuery( document ).ready( function() {

                 function time_valiation( $tr_parent ) {
                 var starting_val = $tr_parent.find( '.weekday_time_from' ).val();
                 var ending_val = $tr_parent.find( '.weekday_time_to' ).val();
                 console.log(starting_val);
                 console.log(ending_val);

                 if( starting_val != '' && ending_val != '' && parseInt( ending_val ) <= parseInt( starting_val ) ) {
                 jQuery( $tr_parent ).next('tr').show().find( 'td:last' ).html( 'Please select `Ending` time greater than `Starting` time or `Starting time less than `Ending` time.' );
                 return false;
                 }
                 else {
                 jQuery( $tr_parent ).next('tr').hide();
                 return true;
                 }

                 }
                 jQuery( '.weekday_time_to' ).change( function() {
                 time_valiation( jQuery( this ).parent().parent() );
                 });
                 jQuery( '.weekday_time_from' ).change( function() {
                 time_valiation( jQuery( this ).parent().parent() );
                 });
                 jQuery( '#redux-form-wrapper' ).submit( function() {
                 var flag = true;

                 for( var i = 0; i < 7; i++ ) {
                 var from_val = jQuery( '.weekday_time_from' ).eq( i ).val();
                 var to_val = jQuery( '.weekday_time_to' ).eq( i ).val();

                 if( from_val != '' && to_val != '' ) {
                 if( jQuery( '.weekday_time_from' ).eq( i ).change == false ) {
                 flag = false;
                 }
                 }
                 else if( from_val != '' && to_val == '' ) {
                 jQuery( '.weekday_time_to' ).eq( i ).parent().parent().next('tr').show().find( 'td:last' ).html( 'Please select `Ending` time.' );
                 flag = false;
                 }
                 else if( from_val == '' && to_val != '' ) {
                 jQuery( '.weekday_time_to' ).eq( i ).parent().parent().next('tr').show().find( 'td:last' ).html( 'Please select `Starting` time.' );
                 flag = false;
                 }
                 }

                 if( ! flag ) {
                 return false;
                 }
                 else {
                 return true;
                 }
                 });
                 });*/
            </script>
        <?php
        }
    }
}
