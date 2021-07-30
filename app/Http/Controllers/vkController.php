<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use VK\Client\VKApiClient;
use App\Models\NotesForUsers;

/**
 * Class vkController
 * @package App\Http\Controllers
 */
class vkController extends Controller
{
    /**
     * @var string|VKApiClient
     */
    private static $vk = '';

    /**
     * vkController constructor.
     */
    function __construct()
    {
        self::$vk = new VKApiClient();
    }

    /**
     * @param array $sort
     * @param string $pagination
     * @return false|string
     */
    public function index()
    {
        $users_in_group = self::$vk->groups()->getMembers(env('VK_API_TOKEN'),[
            'group_id' => env('VK_API_GROUP_ID')
        ]);

        $users = self::$vk->users()->get(env('VK_API_TOKEN'),[
            'user_ids' => $users_in_group['items'],
            'fields' => 'photo_id, city'
        ]);

        $notes = NotesForUsers::get();

        // I don`t like
        for ($i = 0; $i < count($users); $i++)
        {
            for ($j = 0; $j<count($notes); $j++)
            {
                if ($users[$i]['id'] == $notes[$j]['user_id'])
                {
                    $users[$i]['notes'] = $notes[$i];
                }
            }
        }

        return $users;
    }

    /**
     * Show the form for creating a new resource.
     *
     * @return \Illuminate\Http\Response
     */
    public function create()
    {
        //
    }

    /**
     * Store a newly created resource in storage.
     *
     * @param  \Illuminate\Http\Request  $request
     * @return \Illuminate\Http\Response
     */
    public function store(Request $request)
    {
        $vk = self::$vk;
        $status = '';

        if (isset($request['user_id']) && isset($request['note'])) {
            $NoteTable = new NotesForUsers();

            $NoteTable->updateOrInsert(
                ['user_id' => $request['user_id']],
                ['note' => $request['note']]
            );
            $message = 'Your note has been updated: "'.$request['note'].'"';

            $sending_status_ok = $this->send_notification($request['user_id'], $message);

            return $sending_status_ok;
        } else {

            return 400;
        }
    }

    /**
     * Display the specified resource.
     *
     * @param  int  $id
     * @return \Illuminate\Http\Response
     */
    public function show($sort)
    {
        if (in_array($sort, config('validSortingParameters.sorting_option')))
        {
            $sorting_options = [];

            $users = $this->index();
            $sorted_users = [];

            if (isset($sort))
            {
                foreach ($users as $user) {
                    $sorted_users[$user[$sort]] = $user;
                }
            }

            krsort($sorted_users);

            return $sorted_users;

        } else {
            return 400;
        }
    }

    /**
     * Show the form for editing the specified resource.
     *
     * @param  int  $id
     * @return \Illuminate\Http\Response
     */
    public function edit($id)
    {
        //
    }

    /**
     * Update the specified resource in storage.
     *
     * @param  \Illuminate\Http\Request  $request
     * @param  int  $id
     * @return \Illuminate\Http\Response
     */
    public function update(Request $request, $id)
    {
        //
    }

    /**
     * Remove the specified resource from storage.
     *
     * @param  int  $id
     * @return \Illuminate\Http\Response
     */
    public function destroy($id)
    {
        $notes = new NotesForUsers();

        $status = $notes->where('user_id', '=', $id)->delete();
        if ($status == 1)
        {
            $message = 'Your note has been deleted';

            $this->send_notification($id,$message);
        }

        return $status;
    }

    /**
     * @param int $user_id
     * @param string $message
     */
    public function send_notification($user_id, $message)
    {
        $vk = self::$vk;
        $is_send_message = $vk->messages()->isMessagesFromGroupAllowed(env('VK_API_TOKEN'), [
            'group_id' => env('VK_API_GROUP_ID'),
            'user_id' => $user_id
        ]);

        if ($is_send_message['is_allowed'] == 1)
        {
            $vk->messages()->send(
                env('VK_API_TOKEN'),
                [
                    'user_id' => $user_id,
                    'random_id' => random_int(0, 10000),
                    'message' => $message
                ]);
            return 200;
        } else {
            return 403;
        }

    }

}
