<?php
//. cbl_export.php

//. Referer: https://developer.cybozulive.com/doc/current/#id1

//. For XAuth(Ones of Level Z)
$consumer_key           = '(CybozuLive Developer Center Consumer Key)';
$consumer_secret        = '(CybozuLive Developer Center Consumer Secret)';
$xauth_access_token_url = 'https://api.cybozulive.com/oauth/token';
 
$params = array(
    'x_auth_username' => '(CybozuLive Email)',
    'x_auth_password' => '(CybozuLive Password)',
    'x_auth_mode'     => 'client_auth',
);

require_once( 'HTTP/Request2.php' );
require_once( 'HTTP/OAuth/Consumer.php' );

//. Get Access Token via xAuth
try{
    $request = new HTTP_Request2();
    $request->setConfig('ssl_verify_peer', false);
 
    $consumerRequest = new HTTP_OAuth_Consumer_Request();
    $consumerRequest->accept($request);
 
    $oauth = new HTTP_OAuth_Consumer($consumer_key, $consumer_secret);
    $oauth->accept($consumerRequest);
 
    //. Send Request
    $response = $oauth->sendRequest($xauth_access_token_url, $params, HTTP_Request2::METHOD_POST);
 
    //. Check HTTP Status
    if( $response->getStatus() !== 200 ){
        throw new Exception( $response->getBody(), $response->getStatus() );
    }
 
    //. Parse result and retrive access token
    parse_str( $response->getBody(), $access_token_info );
}catch( HTTP_Request2_Exception $hr2e ){
    echo 'Error(HTTP_Request2_Exception):';
    echo $hr2e;
    exit;
}catch( Exception $e ){
    echo 'Error(Exception):';
    echo $e;
    exit;
}

//. Retrieve information from Cybozu Live
try{
    //. Set access token
    $request = new HTTP_Request2();
    $request->setConfig( 'ssl_verify_peer', false );
 
    $consumerRequest = new HTTP_OAuth_Consumer_Request();
    $consumerRequest->accept( $request );
 
    $oauth = new HTTP_OAuth_Consumer($consumer_key,
                                     $consumer_secret,
                                     $access_token_info[oauth_token],
                                     $access_token_info[oauth_token_secret]);
    $oauth->accept( $consumerRequest );

    //. Personal data folder
    $my_folder = '.' . DIRECTORY_SEPARATOR . $params['x_auth_username'];
    echo 'my_folder = ' . $my_folder . "\n";
    if( !file_exists( $my_folder ) ){
        mkdir( $my_folder, 0777 );
    }

    //. Personal schedules
    $req = $oauth->sendRequest( "https://api.cybozulive.com/api/schedule/V2", array(), 'GET' );
    $status = $req->getStatus();
    $xml = $req->getBody();
    if( $status !== 200 ){
        throw new Exception( $xml, $status );
    }

    $schedule_file = $my_folder . DIRECTORY_SEPARATOR . 'schedule.xml';
    echo ' schedule_file = ' . $schedule_file . "\n";
    file_put_contents( $schedule_file, $xml );

    //. Chats(theme)
    $req = $oauth->sendRequest("https://api.cybozulive.com/api/mpChat/V2", array("chat-type"=>"THEME"), 'GET');
    $status = $req->getStatus();
    $xml = $req->getBody();
    if( $status !== 200 ){
        throw new Exception( $xml, $status );
    }

    $chat_file = $my_folder . DIRECTORY_SEPARATOR . 'chat_theme.xml';
    echo ' chat_file(theme) = ' . $chat_file . "\n";
    file_put_contents( $chat_file, $xml );

    //. Chats(direct)
    $req = $oauth->sendRequest("https://api.cybozulive.com/api/mpChat/V2", array("chat-type"=>"DIRECT"), 'GET');
    $status = $req->getStatus();
    $xml = $req->getBody();
    if( $status !== 200 ){
        throw new Exception( $xml, $status );
    }

    $chat_file = $my_folder . DIRECTORY_SEPARATOR . 'chat_direct.xml';
    echo ' chat_file(direct) = ' . $chat_file . "\n";
    file_put_contents( $chat_file, $xml );

    //. ToDos
    $req = $oauth->sendRequest("https://api.cybozulive.com/api/task/V2", array(), 'GET');
    $status = $req->getStatus();
    $xml = $req->getBody();
    if( $status !== 200 ){
        throw new Exception( $xml, $status );
    }

    $todo_file = $my_folder . DIRECTORY_SEPARATOR . 'todo.xml';
    echo ' todo_file = ' . $todo_file . "\n";
    file_put_contents( $todo_file, $xml );

    //. Connections
    $req = $oauth->sendRequest("https://api.cybozulive.com/api/mpAddress/V2", array(), 'GET');
    $status = $req->getStatus();
    $xml = $req->getBody();
    if( $status !== 200 ){
        throw new Exception( $xml, $status );
    }

    $address_file = $my_folder . DIRECTORY_SEPARATOR . 'address.xml';
    echo ' address_file = ' . $address_file . "\n";
    file_put_contents( $address_file, $xml );

    //. ここまで個人データ
    //. ここからグループデータ
 
    //. Groups
    $req0 = $oauth->sendRequest("https://api.cybozulive.com/api/group/V2", array(), 'GET');
    $status = $req0->getStatus();
    $xml0 = $req0->getBody();
    if( $status !== 200 ){
        throw new Exception( $xml0, $status );
    }
 
    $list0 = simplexml_load_string($xml0);
    foreach($list0->entry as $entry0) {
        $group_title = $entry0->title;
        $group_id = $entry0->id;
        $group_summary = $entry0->summary;
        list( $dummy1, $group_id ) = split( ",", $group_id );

        $group_folder = '.' . DIRECTORY_SEPARATOR . $group_id;
        echo 'group_folder = ' . $group_folder . "\n";
        if( !file_exists( $group_folder ) ){
            mkdir( $group_folder, 0777 );
        }

        $group_file = $group_folder . DIRECTORY_SEPARATOR . 'group.xml';
        echo ' group_file = ' . $group_file . "\n";
        file_put_contents( $group_file, $xml0 );

        //. Group member
        $req1 = $oauth->sendRequest("https://api.cybozulive.com/api/gwMemberList/V2", array("group"=>$group_id), 'GET');
        $xml1 = $req1->getBody();
        if ($req1->getStatus() !== 200) {
            throw new Exception($xml1, $req1->getStatus());
        }

        $member_file = $group_folder . DIRECTORY_SEPARATOR . 'member.xml';
        echo ' member_file = ' . $member_file . "\n";
        file_put_contents( $member_file, $xml1 );

        //. Group Board
        $req2 = $oauth->sendRequest("https://api.cybozulive.com/api/board/V2", array("group"=>$group_id,"embed-comment"=>"true"), 'GET');
        $xml2 = $req2->getBody();
        if ($req2->getStatus() !== 200) {
            throw new Exception($xml2, $req2->getStatus());
        }

        $board_file = $group_folder . DIRECTORY_SEPARATOR . 'board.xml';
        echo ' board_file = ' . $board_file . "\n";
        file_put_contents( $board_file, $xml2 );

        //. Attachments in Group Board
        $board_attachments_folder = $group_folder . DIRECTORY_SEPARATOR . 'board_attachments';
        if( !file_exists( $board_attachments_folder ) ){
            mkdir( $board_attachments_folder, 0777 );
        }

        $xml2 = preg_replace( "/<([^>]+?):(.+?)>/", "<$1_$2>", $xml2 );
        $xml2 = preg_replace( "/_\/\//", "://", $xml2 );
        $feed2 = simplexml_load_string( $xml2 );
        foreach( $feed2->entry as $entry2 ){
            $entry_id = $entry2->id;
            list( $dummy1, $dummy2, $dummy3, $entry_id ) = split( ",", $entry_id );

            $board_id_attachments_folder = $board_attachments_folder . DIRECTORY_SEPARATOR . $entry_id;
            if( !file_exists( $board_id_attachments_folder ) ){
                mkdir( $board_id_attachments_folder, 0777 );
            }

            //. Attachments in main entry
            foreach( $entry2->cbl_attachment as $cbl_attachment2 ){
                $attachment_fileName = $cbl_attachment2->attributes()->fileName;
                $attachment_id = $cbl_attachment2->attributes()->id;
                $attachment_entryId = $cbl_attachment2->attributes()->entryId;
                $attachment_type = $cbl_attachment2->attributes()->type;
                //$attachment_number = $cbl_attachment2->attributes()->number;

                $file_path = $board_id_attachments_folder . DIRECTORY_SEPARATOR . $attachment_fileName;
                fileDownload( $oauth, $attachment_entryId, $file_path );
            }

            //. Attachments in comments
            $cbl_comments = $entry2->cbl_comments;
            if( $cbl_comments ){
                $cbl_feedLink = $cbl_comments->cbl_feedLink;
                $cbl_feed = $cbl_feedLink->feed;
                foreach( $cbl_feed->entry as $entry2_ ){
                    $cbl_sequence = $entry2_->attributes()->cbl_sequence;

                    $board_sequence_attachments_folder = $board_id_attachments_folder . DIRECTORY_SEPARATOR . $cbl_sequence;
                    if( !file_exists( $board_sequence_attachments_folder ) ){
                        mkdir( $board_sequence_attachments_folder, 0777 );
                    }

                    foreach( $entry2_->cbl_attachment as $cbl_attachment2_ ){
                        $attachment_fileName = $cbl_attachment2_->attributes()->fileName;
                        $attachment_id = $cbl_attachment2_->attributes()->id;
                        $attachment_entryId = $cbl_attachment2_->attributes()->entryId;
                        $attachment_type = $cbl_attachment2_->attributes()->type;
                        $attachment_number = $cbl_attachment2_->attributes()->number;
    
                        $board_number_attachments_folder = $board_sequence_attachments_folder . DIRECTORY_SEPARATOR . $attachment_number;
                        if( !file_exists( $board_number_attachments_folder ) ){
                            mkdir( $board_number_attachments_folder, 0777 );
                        }

                        $file_path = $board_number_attachments_folder . DIRECTORY_SEPARATOR . $attachment_fileName;
                        fileDownload( $oauth, $attachment_entryId, $file_path );
                    }
                }
            }
        }

        //. Group Events
        $req3 = $oauth->sendRequest("https://api.cybozulive.com/api/gwSchedule/V2", array("group"=>$group_id,"embed-comment"=>"true"), 'GET');
        $xml3 = $req3->getBody();
        if ($req3->getStatus() !== 200) {
            throw new Exception($xml3, $req3->getStatus());
        }

        $schedule_file = $group_folder . DIRECTORY_SEPARATOR . 'schedule.xml';
        echo ' schedule_file = ' . $schedule_file . "\n";
        file_put_contents( $schedule_file, $xml3 );

        //. Group Todos
        $req3 = $oauth->sendRequest("https://api.cybozulive.com/api/gwTask/V2", array("group"=>$group_id,"embed-comment"=>"true"), 'GET');
        $xml3 = $req3->getBody();
        if ($req3->getStatus() !== 200) {
            throw new Exception($xml3, $req3->getStatus());
        }

        $todo_file = $group_folder . DIRECTORY_SEPARATOR . 'todo.xml';
        echo ' todo_file = ' . $todo_file . "\n";
        file_put_contents( $todo_file, $xml3 );

        //. Group Files(UNCLASSIFIED)
        $files_folder = $group_folder . DIRECTORY_SEPARATOR . 'files';
        if( !file_exists( $files_folder ) ){
            mkdir( $files_folder, 0777 );
        }

        $req4 = $oauth->sendRequest("https://api.cybozulive.com/api/gwCabinet/V2", array("group"=>$group_id,"cabinet-folder"=>"UNCLASSIFIED"), 'GET');
        $xml4 = $req4->getBody();
        if ($req4->getStatus() !== 200) {
            throw new Exception($xml4, $req4->getStatus());
        }

        $files_file = $group_folder . DIRECTORY_SEPARATOR . 'files_unclassified.xml';
        echo ' files_file(unclassified) = ' . $files_file . "\n";
        file_put_contents( $files_file, $xml4 );

        $list4 = simplexml_load_string($xml4); 
        foreach($list4->entry as $entry4) {
            $file_title = $entry4->title;
            $file_id = $entry4->id; //. GROUP,2:XXXX,CABINET,2:YYYY
echo 'file_id = ' . $file_id . ', file_title = ' . $file_title . "\n";

            $file_path = $files_folder . DIRECTORY_SEPARATOR . $file_title;
            fileDownload( $oauth, $file_id, $file_path );
        }

        //. Group Files(ATTACH)
        $req4 = $oauth->sendRequest("https://api.cybozulive.com/api/gwCabinet/V2", array("group"=>$group_id,"cabinet-folder"=>"ATTACH"), 'GET');
        $xml4 = $req4->getBody();
        if ($req4->getStatus() !== 200) {
            throw new Exception($xml4, $req4->getStatus());
        }

        $files_file = $group_folder . DIRECTORY_SEPARATOR . 'files_attach.xml';
        echo ' files_file(attach) = ' . $files_file . "\n";
        file_put_contents( $files_file, $xml4 );

        $list4 = simplexml_load_string($xml4); 
        foreach($list4->entry as $entry4) {
            $file_title = $entry4->title;
            $file_id = $entry4->id; //. GROUP,2:XXXX,CABINET,2:YYYY

            $file_path = $files_folder . DIRECTORY_SEPARATOR . $file_title;
            fileDownload( $oauth, $file_id, $file_path );
        }
    }
} catch (HTTP_Request2_Exception $hr2e) {
    echo 'Error(HTTP_Request2_Exception):';
    echo $hr2e;
    exit;
} catch (Exception $e) {
    echo 'Error(Exception):';
    echo $e;
    exit;
}


function fileDownload( $oauth, $file_id, $filepath ){
    echo 'filepath = ' . $filepath . "\n";

/* Error(Exception):exception 'Exception' with message 'oauth_problem=signature_invalid'
    //. file download request
    $req = $oauth->sendRequest("https://api.cybozulive.com/api/fileDownload/V2", array("id"=>$file_id), 'GET');
    $status = $req->getStatus();
    $body = $req->getBody();
    if( $status !== 200 ){
        throw new Exception( $body, $status );
    }

    //. save file
    file_put_contents( $filepath, $body );
*/
}

?>

