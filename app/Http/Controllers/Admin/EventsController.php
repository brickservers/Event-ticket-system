<?php

namespace App\Http\Controllers\Admin;

use Auth;
use App\Event;
use Validator;
use App\Ticket;
use App\Category;
use Illuminate\Http\Request;
use App\Http\Requests\StoreEvent;
use Illuminate\Http\UploadedFile;
use App\Helper\checkAndUploadImage;
use Illuminate\Support\Facades\Log;
use App\Http\Controllers\Controller;
use Illuminate\Support\Facades\Input;
use Intervention\Image\Facades\Image;
use Illuminate\Database\QueryException;
use Illuminate\Support\Facades\Storage;
use App\Helper\checkAndUploadUpdatedImage;

class EventsController extends Controller
{
    use checkAndUploadImage, checkAndUploadUpdatedImage;
    /**
     * Display a listing of the resource.
     *
     * @return \Illuminate\Http\Response
     */
    public function index()
    {
        //log event
        Log::info('Displayed a list of available events in database for user with email:' .' ' .Auth::user()->email .' ' .'to see');
        $events = Event::all();
        return view('admin.events.index', compact('events'));
    }

    /**
     * Show the form for creating a new resource.
     *
     * @return \Illuminate\Http\Response
     */
    public function create()
    {
        //log event
        Log::info('Displayed a form to create an event for User with email:' .' ' .Auth::user()->email);
        $categories = Category::all();
        return view('admin.events.create', compact('categories'));
    }

    /**
     * Store a newly created resource in storage.
     *
     * @param  \Illuminate\Http\Request  $request
     * @return \Illuminate\Http\Response
     */
    public function store(StoreEvent $request)
    {
        //store the request in a $data variable
        $data = $request->all();

        $data['user_id'] = Auth::user()->id;

        //upload and store image
        $imageName = $this->checkAndUploadImage($request, $data);
       
        $data['image'] = $imageName;
        
        try{
        //Event::create($data);
        $createdEvent = Event::create([
            'user_id' => $data['user_id'],
            'category_id' => $data['category_id'],
            'image' => $data['image'],
            'name' => $data['name'],
            'venue' => $data['venue'],
            'description' => $data['description'],
            'actors' => $data['actors'],
            'time' => $data['time'],
            'date' => $data['date'],
            'age' => $data['age'],
            'dresscode' => $data['dresscode']
        ])->tickets()->create([
            'regular' => $data['regular'],
            'vip' => $data['vip'],
            'tableforten' => $data['tableforten'],
            'tableforhundred' => $data['tableforhundred'],
        ]);
        
        }catch(QueryException $e){
            //log error
            Log::error($e->getMessage());
            //return flash session error message to view
            return redirect()->route('system-admin.events.create')->with('error', 'something went wrong');
        }
        //return back
        return redirect()->route('system-admin.events.create')->with('success', 'Event added successfully');
    }

    /**
     * Display the specified resource.
     *
     * @param  int  $id
     * @return \Illuminate\Http\Response
     */
    public function show($id)
    {
        //
    }

    /**
     * Show the form for editing the specified resource.
     *
     * @param  int  $id
     * @return \Illuminate\Http\Response
     */
    public function edit($id)
    {   
        $event = Event::findOrFail($id);
        $eventTicket = Ticket::findOrFail($id);
        $categories = Category::all();
        return view('admin.events.edit',compact('event', 'categories', 'eventTicket'));
    }

    /**
     * Update the specified resource in storage.
     *
     * @param  \Illuminate\Http\Request  $request
     * @param  int  $id
     * @return \Illuminate\Http\Response
     */
    public function update(StoreEvent $request, $id)
    {
        //store all incoming request in a $data variable
        $data = $request->all();
        //to get only the image name from the folder path and extension explode it
        $formerImage = explode('/', $data['imagename']);
       
        $path = 'images/frontend_images/events';
        
        $data['image'] = $this->checkAndUploadUpdatedImage($data, $request);

        $updateEvent = tap(Event::find($id))->update([
            
            'name' => $data['name'],
            'category_id' => $data['category_id'],
            'user_id' => Auth::user()->id,
            'venue' => $data['venue'],
            'description' => $data['description'],
            'date' => $data['date'],
            'time' => $data['time'],
            'actors' => $data['actors'],
            'age' => $data['age'],
            'dresscode' => $data['dresscode'],
            'image' => $data['image'],
        
        ]);

        Ticket::find($updateEvent->id)->update([
            'regular' => $data['regular'],
            'vip' => $data['vip'],
            'tableforten' => $data['tableforten'],
            'tableforhundred' => $data['tableforhundred'],
        ]);
        
        return redirect()->route('system-admin.events.index')->with('success', 'Event updated successfully');
   
    }

    /**
     * Remove the specified resource from storage.
     *
     * @param  int  $id
     * @return \Illuminate\Http\Response
     */
    public function destroy($id)
    {
        //Delete Image If It Exists.
        if (file_exists(Event::find($id)->image)) {
            unlink(Event::find($id)->image);
        }

        //delete the event
        Event::destroy($id);
        //log the event
        log::info('User with email:' .' ' .Auth::user()->email .' ' .'just deleted an event with Id number' .' ' .$id);
        //return flash success message
        return redirect()->route('system-admin.events.index')->with('success', 'Event deleted successfully');
    }

    public function activate($id) {
        //find event with given id and activate it
        Event::find($id)->update([
            'status' => 1
        ]);
        //log the event
        log::info('Event with id of' .' ' .$id .' ' .'just got activated');
        //return flash session success message back to the view.
        return back()->with('success', 'Event successfully activated');
    }

    public function deActivate($id) {
        //find event with given id and activate it
        Event::find($id)->update([
            'status' => 0
        ]);
        //log the event
        log::info('Event with id of' .' ' .$id .' ' .'just got de-activated');
        //return flash session success message back to the view.
        return back()->with('success', 'Event successfully De-activated');
    }

    //public function 
    //Return comments back to events index page
}
