<?php

namespace App\Http\Controllers;

use Carbon\Carbon;

class StreamController extends Controller
{
    /**
     * The stream source.
     *
     * @return \Illuminate\Http\Response
     */
    public function stream(){
        set_time_limit(0);
        // make session read-only
        session_start();
        session_write_close();

        // disable default disconnect checks
        ignore_user_abort(true);

        header('Content-Type: text/event-stream');
        header('Cache-Control: no-cache');
        header("Access-Control-Allow-Origin: *");
        
        $prvic = true;
        $conn = pg_pconnect("dbname=chat user=postgres password=postgres");
        
        if (!$conn) {
            echo "An error occurred.\n";
            exit;
        }
        
        while (true) {
            pg_query($conn, 'LISTEN messages;');
            $notify = pg_get_notify($conn);
            // Every second, send a "ping" event.
            //echo "event: ping\n";

            //echo 'data: ' . stripslashes(json_encode($notify));
            //echo "\n\n";
            $sql = "SELECT u.name as username, m.message as msg, u.id as id FROM messages m
            LEFT JOIN users u ON m.iduser = id ORDER BY idmsg ASC";
            $sql1 = "SELECT u.name as username, m.message as msg, u.id as id FROM messages m
            LEFT JOIN users u ON m.iduser = id ORDER BY idmsg DESC LIMIT 1";

            $result=pg_query($conn, $sql1);
            $result2=pg_query($conn, $sql);

            if($prvic){
                while($row2 = pg_fetch_assoc($result2)){ 
                    echo 'data: {"id": "' . $row2['id'] . '", "username": "' . $row2['username'] . '", "msg": "' . $row2['msg'] . '"}' . "\n\n"; 
                }
                ob_flush();
                flush();
                $prvic=false;
            }

            if (!$notify) {
            //echo 'heartbeat' . "\n\n";
            } else {  
                    while($row = pg_fetch_assoc($result)){ 
                    //echo 'data:' . $row['username'] . ': ' . $row['msg'] . ' ' . $row['id'] . "\n\n";
                    echo 'data: {"id": "' . $row['id'] . '", "username": "' . $row['username'] . '", "msg": "' . $row['msg'] . '"}' . "\n\n";
                    
                }
            ob_flush();
            flush();
            
            }
            
            // Send a simple message at random intervals.
            // Break the loop if the client aborted the connection (closed the page)

            if (connection_aborted()) break;

            sleep(2);
        }
    pg_close($conn);
    }
}