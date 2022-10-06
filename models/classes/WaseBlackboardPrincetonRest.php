<?php
/**
 * This class defines the methods that must be implemented for WASE to communicate with Blackboard at Princeton
 * (using the local Princeton web services).
 *
 *
 * @copyright 2006, 2008, 2013 The Trustees of Princeton University
 * @license For licensing terms, see the license.txt file in the distribution.
 * @author James Chu, jkchu@princeton.edu
 */
class WaseBlackboardPrincetonRest implements WaseLMS
{

    /**
     *
     * * @static
     * * @param string $userid
     *            The userid identifier
     * * @param string $courseid
     *            The course identifier.
     *
     * * @return boolean true if userid/course in session, false if userid/course not in session
     **/
    private static function UserCourseRole_In_Session($userid, $course) {
        if(isset($_SESSION['bb_user_course_array']) == false) {
            $_SESSION['bb_user_course_array'] = array();
        }
        $in_session = false;
        $tag = strtoupper($userid.$course);
        foreach($_SESSION['bb_user_course_array'] as $session_value) {
            //WaseMsg::dMsg('DBG','Info',"Rest UserCourse_In_Session-session_value[$session_value]");
            if(preg_match("/$tag/",$session_value)) {
                $in_session = true;
                break;
            }
        }
        return $in_session;
    }
    /**
     *
     * * @static
     * * @param string $courseid
     *            The course identifier.
     *
     * * @return string - wase pref courseid (to be used for wasepref lookup)
     **/
    private static function Ret_wase_pref_courseid( $courseid) {
        return "T-".$courseid;
    }
    /**
     *
     * * @static
     * * @param string $userid
     *            The userid identifier
     * * @param string $courseid
     *            The course identifier.
     * * @param array $role_array
     *            The role_array to store
     *
     * * @return $my_error blank if no errors, else error_string
     **/
    private static function Load_UserCourseRole_To_Session($userid, $course,$role_array) {
        $my_error = '';
        if(self::UserCourseRole_In_Session($userid,$course)) {
            $my_error="load failed - userid[$userid]course[$course]already in session";
        } else {
            $role_list=implode(",",$role_array);
            $tag=strtoupper($userid.$course);
            $session_value=$tag.'|'.$role_list;
            $_SESSION['bb_user_course_array'][]=$session_value;
            //WaseMsg::dMsg('DBG','Info',"Rest LoadUserCourseRole_To_Session-session_value[$session_value]");
        }
        return $my_error;
    }
    /**
     *
     * * @static
     * * @param string $userid
     *            The userid identifier
     * * @param string $courseid
     *            The course identifier.
     * * @param array $role_array
     *            The role_array to store
     *
     * * @return $my_error blank if no errors, else error_string
     **/
    private static function Ret_UserCourseRole_From_Session($userid, $course) {
        $role_array=array();
        if(self::UserCourseRole_In_Session($userid,$course)) {
            $found_id = 0;
            $tag=strtoupper($userid.$course);
            foreach($_SESSION['bb_user_course_array'] as $session_value) {
                //WaseMsg::dMsg('DBG','Info',"Rest RetUserCourseRole_From_Session-session_value[$session_value]");
                if(preg_match("/$tag/",$session_value)) {
                    $found_it = 1;
                    list($tag,$list_value) = explode('|',$session_value);
                    //WaseMsg::dMsg('DBG','Info',"Rest RetUserCourseRole_From_Session-tag[$tag]list_value[$list_value]");
                    $role_array=explode(',',$list_value);
                    break;
                }
            }
            if($found_it == 0) {
                WaseMsg::logMsg("Blackboard REST Ret_UserCourseRole_From_Session error:  no matching session_value for userid[$userid]course[$course]");
            }
        } else {
            WaseMsg::logMsg("Blackboard REST Ret_UserCourseRole_From_Session error:  no session_value for userid[$userid]course[$course]");
        }
        return $role_array;
    }
    /**
     * Return Blackboard access_token (blank if error encountered)
     * *
     * @return string the access_token ( blank if error encountered)
     **/
    private static function authorize() {
        $access_token="";
        //$rest = new WaseBlackboardPrincetonRest();
        //$auth_path=$rest->AUTH_PATH;
        $auth_path=WaseUtil::getParm('BLACKBOARDREST_AUTH_PATH');
        $hostname=WaseUtil::getParm('BLACKBOARDREST_HOST');
        $user=WaseUtil::getParm('BLACKBOARDREST_KEY');
        $password=WaseUtil::getParm('BLACKBOARDREST_SECRET');
        $url = $hostname.$auth_path;
        $c=curl_init();
        curl_setopt($c,CURLOPT_URL,$url);
        curl_setopt($c,CURLOPT_USERPWD,"$user:$password");
        curl_setopt($c, CURLOPT_POST, true );
        curl_setopt($c, CURLOPT_HTTPHEADER, array(
            'Content-Type: application/x-www-form-urlencoded',
        ));
        curl_setopt($c,CURLOPT_POSTFIELDS,'grant_type=client_credentials');
        curl_setopt($c,CURLOPT_RETURNTRANSFER,1);
        $page = curl_exec($c);
        $http_code = curl_getinfo($c, CURLINFO_HTTP_CODE);
        curl_close($c);
        if($http_code == 200) {
            $token = json_decode($page);
            $access_token = $token->access_token;
        } else {
            WaseMsg::logMsg("Blackboard REST error:  url[$url]http_code[$http_code]");
        }
        return $access_token;
    }
    /**
     * Return the course_name for course_id and access_token
     *
     * * @static
     * * @param string $access_token
     *            The Blackboard access_token.
     * * @param string $courseid
     *            The course identifier.
     *
     * * @return string name and title in format "course_name|course_title" ( blank if error encountered)
     **/
    private static function get_course_name_and_title($access_token , $course_id) {
        $course_name_title="";
        if(($access_token !== "") && ($course_id !== "")) {
            $wase_courseid=self::Ret_wase_pref_courseid($course_id);
            $blackboard_userid=WaseUtil::getParm('BLACKBOARDREST_USERID');
            $pref_course_title=WasePrefs::getPref($blackboard_userid, $wase_courseid);
            //WaseMsg::dMsg('DBG','Info',"Rest get_course_name_title -bb_course_id[$course_id]title[$pref_course_title]");
            if($pref_course_title != "") {
                $course_name_title=$pref_course_title;
            } else {
                $course_id=urlencode($course_id);
                $course_path=WaseUtil::getParm('BLACKBOARDREST_COURSE_PATH');
                $hostname=WaseUtil::getParm('BLACKBOARDREST_HOST');
                $url = $hostname.$course_path."/$course_id";
                $c=curl_init();
                curl_setopt($c,CURLOPT_URL,$url);
                curl_setopt($c, CURLOPT_HTTPHEADER, array(
                    "Authorization: Bearer $access_token"
                ));
                curl_setopt($c,CURLOPT_RETURNTRANSFER,1);
                $page = curl_exec($c);
                //WaseMsg::dMsg('DBG','Info',"Rest get_course_name-page[$page]");
                $http_code = curl_getinfo($c, CURLINFO_HTTP_CODE);
                curl_close($c);
                if($http_code == 200) {
                    $info=json_decode($page);
                    if($info->courseId !== "") {
                        $course_name_title=$info->courseId."|".$info->name;
                        if(WasePrefs::savePref($blackboard_userid, $wase_courseid, $course_name_title)) {
                            //WaseMsg::dMsg('DBG','Info',"Rest get_netid pref saved-bb_userid[$bb_userid]netid[$netid]");
                        } else {
                            WaseMsg::logMsg("Rest get_course_name_title pref save failed-bb_userid[$wase_courseid]");
                        }
                    }
                } else {
                    WaseMsg::logMsg("Blackboard REST error:  url[$url]http_code[$http_code]");
                }
            }

        }
        return $course_name_title;
    }
    /**
     * * Return blackboard course id  to indicate if course_name is valid
     *
     * * @static
     * * @param string $access_token
     *            The blackboard access_token
     * * @param string $course_name
     *            The course identifier.
     *
     * * @return string bb_courseid ( blackboard course id if valid , "" = if not valid)
     **/
    private static function valid_bb_course_name($access_token,$course_name) {
        $bb_course_id = '';
        if(($access_token !== "") && ($course_name !== "")) {
            $course_name=urlencode($course_name);
            $course_path=WaseUtil::getParm('BLACKBOARDREST_COURSE_PATH');
            $hostname=WaseUtil::getParm('BLACKBOARDREST_HOST');
            $url = $hostname.$course_path."/courseId:$course_name";
            $c=curl_init();
            curl_setopt($c,CURLOPT_URL,$url);
            curl_setopt($c, CURLOPT_HTTPHEADER, array(
                "Authorization: Bearer $access_token"
            ));
            curl_setopt($c,CURLOPT_RETURNTRANSFER,1);
            $page = curl_exec($c);
            //WaseMsg::dMsg('DBG','Info',"Rest get_course_name-page[$page]");
            $http_code = curl_getinfo($c, CURLINFO_HTTP_CODE);
            curl_close($c);
            if($http_code == 200) {
                $info=json_decode($page);
                $bb_course_id = $info->id;
            } else {
                WaseMsg::logMsg("Blackboard REST error:  url[$url]http_code[$http_code]");
            }
        }
        return $bb_course_id;
    }
    /**
     * * Return netid  for access_token and blackboard userid
     *
     * * @static
     * * @param string $access_token
     *            The Blackboard access token.
     * * @param string $bb_userid
     *            The blackboard user identifier.
     *
     * * @return string princeton netid ( blank if no netid)
     **/
    public static function get_netid($access_token,$bb_userid) {
        $netid="";
        if(($access_token !== "") && ($bb_userid !== "")) {
            $bb_userid=urlencode($bb_userid);
            //$rest = new WaseBlackboardPrincetonRest();
            //$blackboard_userid="BLACKBOARD_REST";
            $blackboard_userid=WaseUtil::getParm('BLACKBOARDREST_USERID');
            $pref_netid=WasePrefs::getPref($blackboard_userid, $bb_userid);
            //WaseMsg::dMsg('DBG','Info',"netid from prev-bb_userid[$bb_userid]netid[$pref_netid]");
            if($pref_netid != '') {
                $netid = $pref_netid;
                //WaseMsg::dMsg('DBG','Info',"netid from prev-bb_userid[$bb_userid]netid[$netid]");
            } else {
                //$user_path=$rest->USER_PATH;
                $user_path=WaseUtil::getParm('BLACKBOARDREST_USER_PATH');
                $hostname=WaseUtil::getParm('BLACKBOARDREST_HOST');
                $url=$hostname . $user_path ."/$bb_userid";
                $c=curl_init();
                curl_setopt($c,CURLOPT_URL,$url);
                curl_setopt($c, CURLOPT_HTTPHEADER, array(
                    "Authorization: Bearer $access_token"
                ));
                curl_setopt($c,CURLOPT_RETURNTRANSFER,1);
                $page = curl_exec($c);
                $http_code = curl_getinfo($c, CURLINFO_HTTP_CODE);
                curl_close($c);
                if($http_code == 200) {
                    $info=json_decode($page);
                    $netid=$info->userName;
                    if($netid != "") {
                        if(WasePrefs::savePref($blackboard_userid, $bb_userid, $netid)) {
                            //WaseMsg::dMsg('DBG','Info',"Rest get_netid pref saved-bb_userid[$bb_userid]netid[$netid]");
                        } else {
                            WaseMsg::logMsg("Rest get_netid pref save failed-bb_userid[$bb_userid]netid[$netid]");
                        }
                    }
                }
            }

        }
        return $netid;
    }
    private static function ret_role_members_for_bb_course_name($access_token,$course_name,$role) {
        $got_error=0;
        $member_array=array();
        $error_string="";
        //WaseMsg::dMsg('DBG','Info',"ret_role_members_for_bb_course_name[$course_name]role[$role");
        if(($access_token != "") && ($course_name != "") && ($role != "")) {
            $course_name=urlencode($course_name);
            $role=urlencode($role);
            //$rest = new WaseBlackboardPrincetonRest();
            //$course_path=$rest->COURSE_PATH;
            $course_path=WaseUtil::getParm('BLACKBOARDREST_COURSE_PATH');
            $hostname=WaseUtil::getParm('BLACKBOARDREST_HOST');
            $url = $hostname.$course_path."/courseId:$course_name/users?availability.available=Yes&role=$role";
            $c=curl_init();
            curl_setopt($c,CURLOPT_URL,$url);
            curl_setopt($c, CURLOPT_HTTPHEADER, array(
                "Authorization: Bearer $access_token"
            ));
            curl_setopt($c,CURLOPT_RETURNTRANSFER,1);
            $page = curl_exec($c);
            $http_code = curl_getinfo($c, CURLINFO_HTTP_CODE);
            curl_close($c);
            //WaseMsg::dMsg('DBG','Info',"Rest ret_role_members-url[$url]http_code[$http_code]");
            if($http_code == 200) {
                $info=json_decode($page);
                $paging=$info->paging;
                $nextPage=$paging->nextPage;
                $results=$info->results;
                $cnt=count($results);
                //WaseMsg::dMsg('DBG','Info',"Rest ret_role_members-cnt[$cnt]nextPage[$nextPage]");
                if($cnt) {
                    for($i=0;$i<$cnt;$i++) {
                        $bb_userid=$results[$i]->userId;
                        $bb_role=$results[$i]->courseRoleId;
                        if(preg_match("/$bb_role/i",$role)) {
                            $netid = self::get_netid($access_token, $bb_userid);
                            $member_array[] = $netid . "|" . $bb_role;
                            WaseMsg::dMsg('DBG','Info',"Rest ret_role_members-role_match for course[$course_name]for netid[$netid]");
                        } else {
                            //WaseMsg::dMsg('DBG','Info',"Rest ret_role_members-no role_match for course[$course_name]");
                        }
                    }
                }
            }  else {
                $got_error=1;
                $error_string="http_code[$http_code]";
            }
            if($got_error) {
                $member_array=array();
                $member_array[]="ErrorCode.ret_role_members_for_bb_course_name".$error_string;
            }
            //$list=implode(",",$member_array);
            //WaseMsg::dMsg('DBG','Info',"Rest ret_role_members-role[$role]course[$course_name]member_list[$list]");
            return $member_array;
        }
    }
    /**
     * * Return the enrollment members for course_name and access_token
     *
     * * @static
     * * @param string $access_token
     *            The Blackboard access token.
     * * @param string $userid
     *            The course identifier.
     *
     * * @return array the array of members ( no elements if error encountered)
     *  each member element has the format "netid" | "role"
     **/
    private static function ret_members_for_bb_course_name($access_token,$course_name) {
        $got_error=0;
        $member_array=array();
        $error_string="";
        $blackboard_instructor_mode=0;
        if(isset($_SESSION['blackboard_instructor_mode']) && ($_SESSION['blackboard_instructor_mode'] == 1) ) {
            $blackboard_instructor_mode=1;
        }
        //WaseMsg::dMsg('DBG','Info',"ret_members_for_bb_course_name[$course_name]blackboard_instructor_mode[$blackboard_instructor_mode");
        $netid_calls=0;
        if($course_name !== '') {
            $bb_course_id=self::valid_bb_course_name($access_token,$course_name);
            if($bb_course_id == '') {
                $member_array[]="ErrorCode.Invalid_Course";
                return $member_array;
            }
            $got_error=0;
            if($blackboard_instructor_mode) {
                $instructor_roles = array("Instructor");
                $role_cnt  = count($instructor_roles);
                for($i=0;$i<$role_cnt;$i++) {
                    $tmp_member_array=self::ret_role_members_for_bb_course_name($access_token,$course_name,$instructor_roles[$i]);
                    $member_array=array_merge($member_array,$tmp_member_array);
                }
            } else {
                $course_name=urlencode($course_name);
                //$rest = new WaseBlackboardPrincetonRest();
                //$course_path=$rest->COURSE_PATH;
                $course_path=WaseUtil::getParm('BLACKBOARDREST_COURSE_PATH');
                $hostname=WaseUtil::getParm('BLACKBOARDREST_HOST');
                $url = $hostname.$course_path."/courseId:$course_name/users?availability.available=Yes";
                $c=curl_init();
                curl_setopt($c,CURLOPT_URL,$url);
                curl_setopt($c, CURLOPT_HTTPHEADER, array(
                    "Authorization: Bearer $access_token"
                ));
                curl_setopt($c,CURLOPT_RETURNTRANSFER,1);
                $page = curl_exec($c);
                $http_code = curl_getinfo($c, CURLINFO_HTTP_CODE);
                curl_close($c);
                //WaseMsg::dMsg('DBG','Info',"Rest ret_members-url[$url]http_code[$http_code]");
                if($http_code == 200) {
                    $info=json_decode($page);
                    $paging=$info->paging;
                    $nextPage=$paging->nextPage;
                    $stay=0;
                    if($nextPage !== '') {
                        $stay=1;
                    }
                    $results=$info->results;
                    $cnt=count($results);
                    //WaseMsg::dMsg('DBG','Info',"Rest ret_members-cnt[$cnt]nextPage[$nextPage]stay[$stay]");
                    if($cnt) {
                        for($i=0;$i<$cnt;$i++) {
                            $bb_userid=$results[$i]->userId;
                            $bb_role=$results[$i]->courseRoleId;
                            if(($blackboard_instructor_mode == 0) || (($blackboard_instructor_mode == 1) && (preg_match("/instructor|teaching/i",$bb_role)))) {
                                $netid=self::get_netid($access_token,$bb_userid);
                                $netid_calls++;
                            } else {
                                $netid="";
                            }
                            $member_array[]=$netid."|".$bb_role;
                        }
                    }
                    while($stay) {
                        $stay=0;
                        $url=$hostname.$nextPage;
                        $c=curl_init();
                        curl_setopt($c,CURLOPT_URL,$url);
                        curl_setopt($c, CURLOPT_HTTPHEADER, array(
                            "Authorization: Bearer $access_token"
                        ));
                        curl_setopt($c,CURLOPT_RETURNTRANSFER,1);
                        $page = curl_exec($c);
                        $http_code = curl_getinfo($c, CURLINFO_HTTP_CODE);
                        curl_close($c);
                        //WaseMsg::dMsg('DBG','Info',"Rest ret_members-url[$url]http_code[$http_code]");
                        if($http_code == 200) {
                            $info=json_decode($page);
                            $results=$info->results;
                            $cnt=count($results);
                            //WaseMsg::dMsg('DBG','Info',"Rest ret_members-cnt[$cnt]");
                            if($cnt) {
                                for($i=0;$i<$cnt;$i++) {
                                    $bb_userid=$results[$i]->userId;
                                    $bb_role=$results[$i]->courseRoleId;
                                    $netid=self::get_netid($access_token,$bb_userid);
                                    $member_array[]=$netid."|".$bb_role;
                                }
                                $paging=$info->paging;
                                $nextPage=$paging->nextPage;
                                if($nextPage != "") {
                                    $stay=1;
                                }
                                //WaseMsg::dMsg('DBG','Info',"Rest ret_members-nextPage[$nextPage]stay[$stay]");
                            }
                        } else {
                            $got_error=1;
                            $error_string="http_code[$http_code]";
                        }
                    }
                } else {
                    $got_error=1;
                    $error_string="http_code[$http_code]";
                }
            }
        } else {
            $error_string='No_Course_Name';
        }
        if($got_error) {
            $member_array=array();
            $member_array[]="ErrorCode.ret_members_for_bb_course_name".$error_string;
        }
        //WaseMsg::dMsg('DBG','Info',"Rest ret_members-blackboard_instructor_mode[$blackboard_instructor_mode]netid_calls[$netid_calls]");
        return $member_array;
    }
    /**
     * * Return the course_name for userid and access_token
     *
     * * @static
     * * @param string $access_token
     *            The Blackboard access token.
     * * @param string $userid
     *            The course identifier.
     * *  @param string $no_course_name
     *            flag to indicate do not get course_name.
     *
     * * @return array the array of course_names ( no elements if error encountered)
     * * each element has the following format "bb_course_name|bb_course_id|bb_course_role|course_description
     **/
    private static function get_courses($access_token,$userid, $no_course_name) {
        if($no_course_name !== 'Y') {
            $no_course_name = 'N';
        }
        $course_array=array();
        if(($access_token !== "") && ($userid !== "")) {
            $userid=urlencode($userid);
            $user_path=WaseUtil::getParm('BLACKBOARDREST_USER_PATH');
            $hostname=WaseUtil::getParm('BLACKBOARDREST_HOST');
            $username="userName:$userid";
            $url = $hostname.$user_path."/$username/courses?availability.available=Yes";
            $c=curl_init();
            curl_setopt($c,CURLOPT_URL,$url);
            curl_setopt($c, CURLOPT_HTTPHEADER, array(
                "Authorization: Bearer $access_token"
            ));
            curl_setopt($c,CURLOPT_RETURNTRANSFER,1);
            $page = curl_exec($c);
            $http_code = curl_getinfo($c, CURLINFO_HTTP_CODE);
            curl_close($c);
            if($http_code == 200) {
                $info=json_decode($page);
                $paging=$info->paging;
                $nextPage=$paging->nextPage;
                $stay=0;
                $results=$info->results;
                if(is_array($results)) {
                    $cnt=count($results);
                    if($cnt) {
                        for($i=0;$i<$cnt;$i++) {
                            $courseId = $results[$i]->courseId;
                            $courseRoleId=$results[$i]->courseRoleId;
                            if($no_course_name === 'Y') {
                                $bb_course_name = $courseId . '|' .$courseId;
                            } else {
                                $bb_course_name=self::get_course_name_and_title($access_token,$courseId);
                            }

                            if($bb_course_name !== "") {
                                list($course_name,$course_title) = explode("|",$bb_course_name);
                                $course_array[]=$course_name.'|'.$courseId.'|'.$course_title.'|'.$courseRoleId;
                            }
                        }
                        if($nextPage !== '') {
                            $stay=1;
                        }
                        //WaseMsg::dMsg('DBG','Info',"Rest get_courses-nextPage[$nextPage]stay[$stay]");
                        while($stay) {
                            $stay=0;
                            $url = $hostname.$nextPage;
                            //WaseMsg::dMsg('DBG','Info',"Rest get_courses-url[$url]");
                            $c=curl_init();
                            curl_setopt($c,CURLOPT_URL,$url);
                            curl_setopt($c, CURLOPT_HTTPHEADER, array(
                                "Authorization: Bearer $access_token"
                            ));
                            curl_setopt($c,CURLOPT_RETURNTRANSFER,1);
                            $page = curl_exec($c);
                            $http_code = curl_getinfo($c, CURLINFO_HTTP_CODE);
                            curl_close($c);
                            if($http_code == 200) {
                                $info=json_decode($page);
                                $nextPage='';
                                if(property_exists($info,'paging')) {
                                    $paging=$info->paging;
                                    $nextPage=$paging->nextPage;
                                }
                                $stay=0;
                                $results=$info->results;
                                if(is_array($results)) {
                                    $cnt=count($results);
                                    if($cnt) {
                                        for ($i = 0; $i < $cnt; $i++) {
                                            $courseId = $results[$i]->courseId;
                                            $courseRoleId = $results[$i]->courseRoleId;
                                            $bb_course_name = self::get_course_name_and_title($access_token, $courseId);
                                            if ($bb_course_name !== "") {
                                                list($course_name, $course_title) = explode("|", $bb_course_name);
                                                $course_array[] = $course_name . '|' . $courseId . '|' . $course_title . '|' . $courseRoleId;
                                            }
                                        }
                                        if ($nextPage !== '') {
                                            $stay = 1;
                                        }
                                    }
                                }

                            } else {
                                WaseMsg::logMsg("Blackboard REST error:  url[$url]http_code[$http_code]");
                            }
                        }

                    }
                }
                //404 means not found
            } elseif($http_code !== 404) {
                WaseMsg::logMsg("Blackboard REST error:  url[$url]http_code[$http_code]");
            }
        }
        if(count($course_array)) {
            sort($course_array);
        }
        //$list=implode(',',$course_array);
        //WaseMsg::dMsg('DBG','Info',"Rest get_courses-list[$list]");
        return $course_array;
    }
    /**
     * Return the role of a given user in a given course.
     *
     * @static
     *
     * @param string $courseid
     *            The course identifier.
     * @param string $userid
     *            The user identifier.
     *
     * @return array the roles a user has in a course; if not in course, or course does not exist,
     *         the first element will start with the string 'ErrorCodes'. If the user is in the
     *         course, each array element will have one of the user's roles in the course.  Example:
     *
     *         row 1: STUDENT
     *         row 2: ASSISTANT
     */
    public static function getUserRole($userid, $course)
    {
        //WaseMsg::dMsg('DBG','Info',"Rest getUserRole-userid[$userid]course[$course]");
        $role_array = array();
        //WaseUtil has static function IsCourseValid($courseid)
        // which calls getUserRole with userid of xxxxxxxx
        // $result = $lms->getUserRole('xxxxxxxx', $courseid);
        $got_error=1;
        $error_string="";
        $access_token = self::authorize();
        if(($userid !== '') && ($course !== '') && ($access_token !== '')) {
            $bb_course_id = self::valid_bb_course_name($access_token,$course);
            if ($userid === 'xxxxxxxx') {
                // so just check to see if course is a valid bb course
                if ($bb_course_id != '') {
                    $got_error = 0;
                    $role_array[] = "VALID";
                } else {
                    $error_string = "INVALID_COURSE";
                }
            } else {
                if(self::UserCourseRole_In_Session($userid, $course)) {
                    $got_error=0;
                    $role_array=self::Ret_UserCourseRole_From_Session($userid,$course);
                    //WaseMsg::dMsg('DBG','Info',"Rest getUserRole-load role from session for userid[$userid]course[$course]");
                } else {
                    $load_array=array();
                    $no_course_name='Y';
                    $tmp_course_array = self::get_courses($access_token, $userid,$no_course_name);
                    $cnt = count($tmp_course_array);
                    if ($cnt) {
                        $got_error = 0;
                        for ($i = 0; $i < $cnt; $i++) {
                            list($course_name, $course_id, $course_title, $role) = explode('|', $tmp_course_array[$i]);
                            if ($bb_course_id === $course_id) {
                                $role_array[] = $role;
                                $load_array[] = $role;
                            }
                        }
                    } else {
                        $load_array[]="ErrorCodes".$error_string;
                    }
                    $my_error=self::Load_UserCourseRole_To_Session($userid, $course,$load_array);
                    if($my_error != '') {
                        WaseMsg::logMsg("Blackboard REST LoadUserCourseRole_To_Array error[my_error]");
                    }
                }
            }
        } else {
            $error_string='No_Userid_Or_No_Course_Or_No_Token';
        }
        if($got_error) {
            $role_array = array();
            $role_array[]="ErrorCodes".$error_string;
        }
        //$list=implode(",",$role_array);
        //WaseMsg::dMsg('DBG','Info',"Rest getUserRole-list[$list]");
        return $role_array;

    }
    /**
     * Return list of courses in which a given user is enrolled.
     *
     * @static
     *
     * @param string $userid
     *            the given userid
     *
     * @return array the courses a user is enrolled in; if not in course, or user does not exist,
     *         the first element will start with the string 'ErrorCodes'. If user is in at
     *         least one course, the array will contain a set of rows, each row having the course name,
     *         course id and course title, all seperated by the '|' symbol.  Example:
     *
     *
     *         row 1: Economics 101|ECON101|Macro Economics
     *         row 2: Math 110|MAT110|Introduction to Calculus
     *
     */
    public static function getEnrollments($userid)
    {
        $got_error=0;
        //WaseMsg::dMsg('DBG','Info',"Rest getEnrollments-userid[$userid]");
        $access_token=self::authorize();
        $course_array = array();
        if($access_token !== "") {
            $no_course_name='N';
            $tmp_course_array=self::get_courses($access_token,$userid,$no_course_name);
            $cnt=count($tmp_course_array);
            for($i=0;$i<$cnt;$i++) {
                list($course_name,$course_id,$course_title,$role) = explode('|',$tmp_course_array[$i]);
                $course_array[]=$course_name.'|'.$course_name.'|'.$course_title;
            }
        } else {
            $got_error=1;
        }
        if($got_error) {
            $course_array[]="ErrorCodes";
        }
        //$course_list=implode(",",$course_array);
        //WaseMsg::dMsg('DBG','Info',"Rest getEnrollments-userid[$userid]course_list[$course_list]");
        return $course_array;
    }
    /**
     * Return list of users enrolled in a course, along with their roles.
     *
     * @static
     *
     * @return array the users enrolled in a given course; if the course does not exist, the first
     *         element of the array will contain "ErrorCodes", otherwise the array will contain a set of rows,
     *         each row having the userid and primary role, seperated by the '|' symbol. Example:
     *
     *          *
     *         row 1: SGOLDSTEIN|Student
     *         row 2: RCKELLY|Instructure
     *
     */
    public static function getCourseMembership($course_name)
    {
        //WaseMsg::dMsg('DBG', 'Info', "Rest getCourseMembership-course_name[$course_name]");
        $got_error = 1;
        $role_array = array();
        $error_string="";
        if ($course_name !== "") {
            $access_token=self::authorize();
            if($access_token !== '') {
                $got_error=0;
                $role_array=self::ret_members_for_bb_course_name($access_token,$course_name);
            } else {
                $error_string="No_Token";
            }
        } else {
            $error_string="No Course_Name";
        }
        if ($got_error) {
           $role_array = array();
           $role_array[] = "ErrorCodes".$error_string;
        }
        //$list=implode(",",$role_array);
        //WaseMsg::dMsg('DBG', 'Info', "Rest getCourseMembership-list[$list]");
        return $role_array;
    }
    /**
     * Register WASE as a tool.
     *
     * The Princeton Blackboard web services use static registration, so this method need not be called.
     * @static
     *
     * @return bool true if registered, else false
     *
     */
    public static function register()
    {
        return TRUE;
    }
}
?>