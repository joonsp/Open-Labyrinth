<?php

/**
 * Open Labyrinth [ http://www.openlabyrinth.ca ]
 *
 * Open Labyrinth is free software: you can redistribute it and/or modify
 * it under the terms of the GNU General Public License as published by
 * the Free Software Foundation, either version 3 of the License, or
 * (at your option) any later version.
 *
 * Open Labyrinth is distributed in the hope that it will be useful,
 * but WITHOUT ANY WARRANTY; without even the implied warranty of
 * MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
 * GNU General Public License for more details.
 *
 * You should have received a copy of the GNU General Public License
 * along with Open Labyrinth.  If not, see <http://www.gnu.org/licenses/>.
 *
 * @copyright Copyright 2012 Open Labyrinth. All Rights Reserved.
 *
 */
defined('SYSPATH') or die('No direct script access.');

class Controller_WebinarManager extends Controller_Base {

    public function before() {
        parent::before();

        Breadcrumbs::add(Breadcrumb::factory()->set_title(__('Scenario Management'))->set_url(URL::base().'webinarManager'));
    }

    public function action_index()
    {
        $user       = Auth::instance()->get_user();
        $userType   = $user->type->name;

        $this->templateData['webinars'] = ($userType == 'superuser' OR $userType == 'Director')
            ? DB_ORM::model('webinar')->getAllWebinars()
            : DB_ORM::model('webinar')->getAllWebinars($user->id);
        $this->templateData['center'] = View::factory('webinar/view')->set('templateData', $this->templateData);

        $this->template->set('templateData', $this->templateData);
    }

    public function action_my() {
        $this->templateData['webinars'] = DB_ORM::model('webinar')->getWebinarsForUser(Auth::instance()->get_user()->id);

        $this->templateData['center'] = View::factory('webinar/my');
        $this->templateData['center']->set('templateData', $this->templateData);

        unset($this->templateData['right']);
        $this->template->set('templateData', $this->templateData);
    }

    public function action_add()
    {
        Breadcrumbs::add(Breadcrumb::factory()->set_title(__('Create Scenario'))->set_url(URL::base() . 'webinarManager/add'));

        $this->templateData['users']    = DB_ORM::model('user')->getAllUsersAndAuth('ASC');
        $this->templateData['groups']   = DB_ORM::model('group')->getAllGroups();
        $this->templateData['maps']     = (Auth::instance()->get_user()->type->name == 'superuser')
            ? DB_ORM::model('map')->getAllEnabledMap()
            : DB_ORM::model('map')->getAllEnabledAndAuthoredMap(Auth::instance()->get_user()->id);
        $this->templateData['forums']   = DB_ORM::model('dforum')->getAllForums(1,0); // Type of Sort, 1 = Name , 0 - ASC
        $this->templateData['center']   = View::factory('webinar/webinar')->set('templateData', $this->templateData);
        $this->template->set('templateData', $this->templateData);
    }

    public function action_edit()
    {
        Breadcrumbs::add(Breadcrumb::factory()->set_title(__('Edit Scenario'))->set_url(URL::base().'webinarManager/edit'));

        $webinarId = $this->request->param('id', null);
        $this->templateData['maps']    = (Auth::instance()->get_user()->type->name == 'superuser')
            ? DB_ORM::model('map')->getAllEnabledMap()
            : DB_ORM::model('map')->getAllEnabledAndAuthoredMap(Auth::instance()->get_user()->id);
        $this->templateData['webinar'] = DB_ORM::model('webinar', array($webinarId));

        $this->templateData['experts']  = array();
        foreach (DB_ORM::select('Webinar_User')->where('webinar_id', '=', $webinarId)->query()->as_array() as $wUserObj)
        {
            if ($wUserObj->expert == 1) $this->templateData['experts'][] = $wUserObj->user_id;
        }

        $existUsers = array();
        if(count($this->templateData['webinar']->users) > 0)
        {
            foreach($this->templateData['webinar']->users as $webinarUser) {
                $existUsers[] = $webinarUser->user_id;
            }
        }

        $existGroups = array();
        if($this->templateData['webinar'] != null && count($this->templateData['webinar']->groups) > 0) {
            foreach($this->templateData['webinar']->groups as $webinarGroup) {
                $existGroups[] = $webinarGroup->group_id;
            }
        }

        $this->templateData['users']  = DB_ORM::model('user')->getAllUsersAndAuth('ASC', $existUsers);
        $this->templateData['groups'] = DB_ORM::model('group')->getAllGroups('ASC', $existGroups);

        $allUsers = DB_ORM::model('user')->getAllUsersAndAuth('ASC');
        if($allUsers != null && count($allUsers) > 0) {
            foreach($allUsers as $user) {
                $this->templateData['usersMap'][$user['id']] = $user;
            }
        }
        $this->templateData['center'] = View::factory('webinar/webinar')->set('templateData', $this->templateData);
        $this->template->set('templateData', $this->templateData);
    }

    public function action_showStats() {
        $webinarId   = $this->request->param('id', null);
        $step   = $this->request->param('id2', null);
        $dateId   = $this->request->param('id3', null);
        $webinarStepMap = array();

        if($webinarId == null || $dateId == null) {
            Request::initial()->redirect(URL::base() . 'webinarmanager/index');
        } else {


            $webinarData = array();
            $usersMap    = array();
            $webinar     = DB_ORM::model('webinar', array((int)$webinarId));

            if($webinar != null && count($webinar->users) && count($webinar->maps) > 0) {
                foreach($webinar->users as $webinarUser) {
                    if(!isset($usersMap[$webinarUser->user_id])) {
                        $usersMap[$webinarUser->user_id] = $webinarUser->user;
                    }

                    foreach($webinar->maps as $webinarMap) {
                        $webinarData[$webinarUser->user_id][$webinarMap->step][$webinarMap->map_id]['map']    = DB_ORM::model('map', array((int)$webinarMap->map_id));

                        if($webinarMap->step <= $step) {
                            $webinarData[$webinarUser->user_id][$webinarMap->step][$webinarMap->map_id]['status'] = DB_ORM::model('statistics_user_session')->isUserFinishMap($webinarMap->map_id, $webinarUser->user_id, $webinar->id, $step, $dateId);
                        } else {
                            $webinarData[$webinarUser->user_id][$webinarMap->step][$webinarMap->map_id]['status'] = 0;
                        }

                        $webinarData[$webinarUser->user_id][$webinarMap->step][$webinarMap->map_id]['user']   = $webinarUser->user;
                    }
                }
                if($webinar->steps != null && count($webinar->steps) > 0) {
                    foreach($webinar->steps as $webinarStep) {
                        $webinarStepMap[$webinarStep->id] = $webinarStep;
                    }
                }

            }

            $this->templateData['webinar']     = $webinar;
            $this->templateData['webinarStepMap'] = $webinarStepMap;

            foreach ($this->templateData['webinar']->users as $user) {
                DB_ORM::model('webinar_user')->updateInclude4R($user->id, 1);

                $this->templateData['includeUsersData'][$user->user_id] = $user->id;
                $this->templateData['includeUsers'][$user->user_id] = $user->include_4R;
            }

            $this->templateData['usersMap']    = $usersMap;
            $this->templateData['webinarData'] = $webinarData;
            $this->templateData['step'] = $step;
            $this->templateData['dateId'] = $dateId;

            $date = DB_ORM::model('statistics_user_datesave', array($dateId));
            $date  = date('Y-m-d H:i:s',$date->date_save);

            Breadcrumbs::add(Breadcrumb::factory()->set_title('Statistics for ' .$webinar->title)->set_url(URL::base() . 'webinarManager/statistics/'.$webinarId));
            Breadcrumbs::add(Breadcrumb::factory()->set_title($date)->set_url(URL::base() . 'webinarManager/progress'));

            $allUsers = DB_ORM::model('user')->getAllUsersAndAuth('ASC');
            if($allUsers != null && count($allUsers) > 0) {
                foreach($allUsers as $user) {
                    $this->templateData['usersAuthMap'][$user['id']] = $user;
                }
            }

            $this->templateData['center'] = View::factory('webinar/showStats');
            $this->templateData['center']->set('templateData', $this->templateData);

            unset($this->templateData['right']);
            $this->template->set('templateData', $this->templateData);
        }
    }

    public function action_progress()
    {
        $webinarId = $this->request->param('id', null);

        if($webinarId == null) Request::initial()->redirect(URL::base().'webinarmanager/index');

        Breadcrumbs::add(Breadcrumb::factory()->set_title(__('Statistic of Scenario'))->set_url(URL::base() . 'webinarManager/progress'));

        $wData          = array();
        $usersMap       = array();
        $webinar        = DB_ORM::model('webinar', array((int)$webinarId));
        $webinarStepMap = array();

        if($webinar != null && count($webinar->users) && count($webinar->maps) > 0)
        {
            foreach($webinar->users as $wUser)
            {
                $wUserId = $wUser->user_id;
                $userType =DB_ORM::model('User', array($wUserId))->type_id;
                if( ! isset($usersMap[$wUserId])) $usersMap[$wUserId] = $wUser->user;

                $this->templateData['includeUsersData'][$wUserId] = $wUser->id;
                $this->templateData['includeUsers'][$wUserId] = $wUser->include_4R;
                if ($userType != 1) $this->templateData['experts'][$wUserId] = $wUser->expert;

                foreach($webinar->maps as $wMapObj)
                {
                    $wStep          = $wMapObj->step;
                    $wMapId         = $wMapObj->map_id;
                    $wCurrentStep   = $webinar->current_step;

                    $wData[$wUserId][$wStep][$wMapId]['map']    = DB_ORM::model('map', array((int)$wMapId));
                    $wData[$wUserId][$wStep][$wMapId]['status'] = ($wStep <= $wCurrentStep) ? DB_ORM::model('user_session')->isUserFinishMap($wMapId, $wUserId, $webinar->id, $wCurrentStep) : 0;
                    $wData[$wUserId][$wStep][$wMapId]['user']   = $wUser->user;
                }
            }
            if(count($webinar->steps) > 0)
            {
                foreach($webinar->steps as $webinarStep)
                {
                    $webinarStepMap[$webinarStep->id] = $webinarStep;
                }
            }
        }

        $this->templateData['scenario']       = DB_ORM::select('Webinar')->query()->as_array();
        $this->templateData['webinarStepMap'] = $webinarStepMap;
        $this->templateData['webinar']        = $webinar;
        $this->templateData['usersMap']       = $usersMap;
        $this->templateData['webinarData']    = $wData;

        $allUsers = DB_ORM::model('user')->getAllUsersAndAuth('ASC');
        if(count($allUsers) > 0)
        {
            foreach($allUsers as $user)
            {
                $this->templateData['usersAuthMap'][$user['id']] = $user;
            }
        }

        $this->templateData['center'] = View::factory('webinar/statistic')->set('templateData', $this->templateData);
        $this->template->set('templateData', $this->templateData);
    }

    public function action_statistic() {
        Request::initial()->redirect(URL::base() . 'webinarmanager/progress/' . $this->request->param('id', null));
    }

    public function action_statistics() {
        $webinarId   = $this->request->param('id', null);

        if($webinarId == null) {
            Request::initial()->redirect(URL::base() . 'webinarmanager/index');
        } else {
            $this->templateData['history'] = DB_ORM::model('statistics_user_session')->getDateSaveByWebinarId($webinarId);

            $webinar = DB_ORM::model('webinar', array((int)$webinarId));

            $this->templateData['webinar']     = $webinar;
            Breadcrumbs::add(Breadcrumb::factory()->set_title('Statistics for ' . $webinar->title)->set_url(URL::base() . 'webinarManager/statistics'));

            $this->templateData['center'] = View::factory('webinar/all');
            $this->templateData['center']->set('templateData', $this->templateData);
            $this->template->set('templateData', $this->templateData);
        }
    }

    public function action_publishStep() {
        $webinarId   = $this->request->param('id', null);
        $webinarStep = $this->request->param('id2', null);
        $dateId = $this->request->param('id3', null);
        $webinar     = DB_ORM::model('webinar', array((int)$webinarId));

        if($webinar != null && $webinarStep != null && $webinarStep > 0) {
            $jsonObject = null;
            if($webinar->publish == null) {
                $jsonObject = array();
            } else {
                $jsonObject = json_decode($webinar->publish);
            }

            if(!in_array($webinarId . '-' . $webinarStep, $jsonObject)) {
                $jsonObject[] = $webinarId . '-' . $webinarStep;

                $webinar->publish = json_encode($jsonObject);
                $webinar->save();

                if($webinar->forum_id > 0) {
                    DB_ORM::model('dforum_messages')->createMessage($webinar->forum_id, '<a href="' . URL::base() . 'webinarManager/stepReport/' . $webinarId . '/' . $webinarStep .'/'. $dateId. '">Step ' . $webinarStep . ' 4R Report</a>');
                }
            }

            Request::initial()->redirect(URL::base() . 'webinarmanager/showStats/' . $webinarId . '/' . $webinarStep . '/' . $dateId);
        } else {
            Request::initial()->redirect(URL::base() . 'webinarmanager/index');
        }
    }

    public function action_render() {
        $webinarId = $this->request->param('id', null);

        $this->templateData['webinar'] = DB_ORM::model('webinar', array((int)$webinarId));
        if($this->templateData['webinar'] != null && count($this->templateData['webinar']->steps) > 0) {
            foreach($this->templateData['webinar']->maps as $webinarMap) {
                if($webinarMap->step <= $this->templateData['webinar']->current_step) {
                    $this->templateData['mapsMap'][$webinarMap->step][$webinarMap->map_id] = DB_ORM::model('user_session')->isUserFinishMap($webinarMap->map_id, Auth::instance()->get_user()->id, $webinarId, $this->templateData['webinar']->current_step);
                } else {
                    $this->templateData['mapsMap'][$webinarMap->step][$webinarMap->map_id] = 0;
                }
            }
        }

        $this->templateData['center'] = View::factory('webinar/render');
        $this->templateData['center']->set('templateData', $this->templateData);

        unset($this->templateData['right']);
        $this->template->set('templateData', $this->templateData);
    }

    public function action_delete() {
        DB_ORM::model('webinar')->deleteWebinar($this->request->param('id', null));

        Request::initial()->redirect(URL::base() . 'webinarmanager/index');
    }

    public function action_save()
    {
        DB_ORM::model('webinar')->saveWebinar($this->request->post());
        Request::initial()->redirect(URL::base().'webinarmanager/index');
    }

    public function action_changeStep() {
        $webinarId = $this->request->param('id' , null);
        $step      = $this->request->param('id2', null);
        $redirect  = $this->request->param('id3', null);

        DB_ORM::model('webinar')->changeWebinarStep($webinarId, $step);

        if ($redirect == null){
            Request::initial()->redirect(URL::base() . 'webinarmanager/index');
        } else {
            Request::initial()->redirect(URL::base() . 'webinarmanager/progress/'.$webinarId);
        }
    }

    public function action_stepReport() {
        $webinarId = $this->request->param('id', null);
        $stepKey   = $this->request->param('id2', null);
        $dateId   = $this->request->param('id3', null);

        if($webinarId != null && $webinarId > 0 && $stepKey != null && $stepKey > 0) {
            $webinar = DB_ORM::model('webinar', array((int)$webinarId));
            $isExistAccess = false;

            if(Auth::instance()->get_user()->id == $webinar->author_id || Auth::instance()->get_user()->type->name == 'superuser') {
                $isExistAccess = true;
            }

            if(!$isExistAccess && $webinar->publish != null) {
                $jsonObject = json_decode($webinar->publish);

                $isExistAccess = in_array($webinarId . '-' . $stepKey, $jsonObject);
            }

            if($isExistAccess) {
                $report  = new Report_4R(new Report_Impl_PHPExcel(), $webinar->title);
                $notIncludUsers = DB_ORM::model('webinar_user')->getNotIncludedUsers($webinar->id);
                if($webinar != null && count($webinar->maps) > 0) {
                    foreach($webinar->maps as $webinarMap) {
                        if($webinarMap->step == $stepKey) {
                            $report->add($webinarMap->map_id, $webinar->id, $stepKey, $notIncludUsers,$dateId);
                        }
                    }
                }
                $report->generate();

                $report->get();
            } else {
                Request::initial()->redirect(URL::base() . 'home/index');
            }
        } else {
            Request::initial()->redirect(URL::base() . 'webinarmanager/index');
        }
    }

    public function action_mapReport() {
        $webinarId = $this->request->param('id', null);
        $mapKey    = $this->request->param('id2', null);
        $dateId    = $this->request->param('id3', null);

        if($webinarId != null && $webinarId > 0 && $mapKey != null && $mapKey > 0) {
            $webinar = DB_ORM::model('webinar', array((int)$webinarId));
            $map = DB_ORM::model('map', array((int)$mapKey));

            $notIncludUsers = DB_ORM::model('webinar_user')->getNotIncludedUsers($webinar->id);

            $report  = new Report_4R(new Report_Impl_PHPExcel(), $map->name);
            $report->add($mapKey, $webinar->id, '' , $notIncludUsers, $dateId);
            $report->generate();

            $report->get();
        } else {
            Request::initial()->redirect(URL::base() . 'webinarmanager/index');
        }
    }

    public function action_mapReportSCT()
    {
        $webinarId          = $this->request->param('id', null);
        $mapId              = $this->request->param('id2', null);
        $expertWebinarId    = $this->request->param('id3', null);

        if ($webinarId == null AND $mapId == null) Request::initial()->redirect(URL::base().'webinarmanager/index');

        $mapName = DB_ORM::model('map', array((int)$mapId))->name;

        $notIncludeUsers = DB_ORM::model('webinar_user')->getNotIncludedUsers($webinarId);

        $report                     = new Report_SCT(new Report_Impl_PHPExcel(), 'SCT Report '.$mapName);
        $report->expertWebinarId    = $expertWebinarId;
        $report->users              = DB_ORM::model('Webinar_User')->getUsers($webinarId);
        $report->add($mapId, $webinarId, '' , $notIncludeUsers);
        $report->generate();
        $report->get();
    }

    public function action_stepReportSCT()
    {
        $webinarId          = $this->request->param('id', null);
        $stepKey            = $this->request->param('id2', null);
        $expertWebinarId    = $this->request->param('id3', null);

        if ($webinarId == null AND $stepKey != null) Request::initial()->redirect(URL::base().'webinarmanager/index');

        $webinar = DB_ORM::model('webinar', array((int)$webinarId));
        $isExistAccess = false;

        if (Auth::instance()->get_user()->id == $webinar->author_id || Auth::instance()->get_user()->type->name == 'superuser') $isExistAccess = true;

        if ( ! $isExistAccess AND $webinar->publish != null)
        {
            $jsonObject = json_decode($webinar->publish);
            $isExistAccess = in_array($webinarId . '-' . $stepKey, $jsonObject);
        }

        if($isExistAccess)
        {
            $notIncludUsers = DB_ORM::model('webinar_user')->getNotIncludedUsers($webinar->id);

            $report  = new Report_SCT(new Report_Impl_PHPExcel(), $webinar->title);
            $report->experts = DB_ORM::model('Webinar_User')->getExperts($expertWebinarId);
            $report->users = DB_ORM::model('Webinar_User')->getUsers($webinarId);
            if($webinar != null && count($webinar->maps) > 0) {
                foreach($webinar->maps as $webinarMap) {
                    if($webinarMap->step == $stepKey) {
                        $report->add($webinarMap->map_id, $webinar->id, $stepKey, $notIncludUsers);
                    }
                }
            }
            $report->generate();
            $report->get();
        }
        else Request::initial()->redirect(URL::base().'home/index');
    }

    public function action_play() {
        $webinarId = $this->request->param('id', null);
        $step      = $this->request->param('id2', null);
        $mapId     = $this->request->param('id3', null);

        if($webinarId != null && $webinarId > 0 && $step != null && $step > 0) {
            Session::instance()->set('webinarId', $webinarId);
            Session::instance()->set('step', $step);
        }

        Request::initial()->redirect(URL::base() . 'renderLabyrinth/index/' . $mapId);
    }

    public function action_reset() {
        $webId = $this->request->param('id', null);

        $dataStatisticsIds = DB_ORM::model('statistics_user_session')->getSessionByWebinarId($webId);
        list($data, $ids) = DB_ORM::model('user_session')->getSessionByWebinarId($webId,$dataStatisticsIds);

        if (count($data) > 0) {
            // Save Statistics
            DB_ORM::model('statistics_user_session')->saveWebInarSession( $data );
            DB_ORM::model('statistics_user_sessiontrace')->saveWebInarSessionTraces( $ids );
            DB_ORM::model('statistics_user_response')->saveWebInarResponse( $ids );
        }

        DB_ORM::model('webinar')->resetWebinar($webId);

        Request::initial()->redirect(URL::base() . 'webinarmanager/index');
    }

    public function action_updateInclude4R() {
        $id = $this->request->param('id', null);
        $include = $this->request->param('id2', null);

        DB_ORM::model('webinar_user')->updateInclude4R($id, $include);

        return true;

    }

    public function action_updateExpert()
    {
        $id     = $this->request->param('id', null);
        $expert = $this->request->param('id2', null);

        DB_ORM::model('Webinar_User')->updateExpert($id, $expert);
        return true;
    }

    public function action_getMapByWebinar()
    {
        $wId = $this->request->param('id', null);
        $mapsId = array();
        foreach (DB_ORM::select('Webinar_Map')->where('webinar_id', '=', $wId)->query()->as_array() as $wObj)
        {
            $mapsId[] = $wObj->map_id;
        }
        echo(json_encode($mapsId));
        exit;
    }
}