<?php

namespace App\Http\Controllers\Admin;


use Validator;

use Illuminate \{
    Http\Request,
        Support\Facades\DB,
        Support\Facades\Notification
};  //php7 grouping use statements

use App \{
    User,
        Notifications\generalNotification,
        Http\Controllers\Controller
};  //php7 grouping use statements

use App\Repositories\Contracts \{
    NotificationRepoInterface,
        UserRepoInterface
}; //php7 grouping use statements

class notificationController extends Controller
{
    protected $userRepo;
    protected $notificationRepo;

    public function __construct(NotificationRepoInterface $notificationRepo, UserRepoInterface $userRepo)
    {
        $this->notificationRepo = $notificationRepo;
        $this->userRepo = $userRepo;
    }

    /**
     * Show the form for creating a new resource.
     *
     * @return \Illuminate\Http\Response
     */
    public function create()
    {
        $noOfNotifications = $this->notificationRepo->getTotalNotifications();
        $noOfUsers = $this->userRepo->getTotalUsers();
        $sentNotifcations = round(($noOfNotifications / $noOfUsers)) / 2;
        $allNotifications = $this->notificationRepo->getNotifications();
        $readNotifications = $this->notificationRepo->getReadNotifications();
        return view('admin.database_notification.create', compact('sentNotifcations', 'readNotifications', 'allNotifications'));
    }

    /**
     * Store a newly created resource in storage.
     *
     * @param  \Illuminate\Http\Request  $request
     * @return \Illuminate\Http\Response
     */
    public function store(Request $request)
    {
        $msg = [
            'message.required' => 'Please enter the message to be sent'
        ];

        Validator::make($request->all(), [
            'message' => 'required',
        ], $msg)->validate();

        $users = $this->userRepo->getUser();
        Notification::send($users, new generalNotification($request->message, $users));
        return back()->with('success', 'Notification has been sent');
    }

    public function markAsRead()
    {
        Auth::user()->unreadNotifications->markAsRead();
        return back();
    }

    public function deleteNotification()
    {
        DB::table('notifications')->delete();
        return back()->with('success', 'All notification deleted successfully');
    }

    public function viewNotifications()
    {
        $allNotifications = $this->notificationRepo->getNotificationsInArrayFormat();
        return view('admin.database_notification.create', compact('allNotifications'));
    }

}
