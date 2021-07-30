<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use VK\Client\VKApiClient;
use App\Models\NotesForUsers;

class GetUserDataController extends Controller
{
    private static $vk = '';

    function __construct()
    {
        self::$vk = new VKApiClient();
    }

    public function index(Request $request)
    {
        if (isset($request['user_ids']))
        {
            $notes = NotesForUsers::get();

            $users = self::$vk->users()->get(env('VK_API_TOKEN'),[
                'user_ids' => $request['user_ids'],
                'fields' => 'photo_id, city'
            ]);

            $is_member = self::$vk->groups()->isMember(env('VK_API_TOKEN'), [
                'group_id' => env('VK_API_GROUP_ID'),
                'user_ids' => $request['user_ids'],
                'extended' => false
            ]);

            $notes = NotesForUsers::get();

            // I don`t like P.S. C увиличением количества подписанных пользователей скорость загрузки не изменяется, (хм... Странно)
            foreach ($is_member as $item) {
                if(!empty($item['member']))
                {
                    for ($i = 0; $i < count($users); $i++)
                    {
                        if($users[$i]['id'] == $item['user_id']) {
                            $users[$i]['is_member'] = true;
                            foreach ($notes as $note)
                            {
                                if ($users[$i]['id'] == $note['user_id'])
                                {
                                    $users[$i]['note'] = $note['note'];

                                }
                            }
                        }
                    }
                }
            }
        }

        return $users;
    }
}
