<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Models\Event;
use App\Models\User;
use PhpParser\Node\Stmt\TryCatch;


class EventController extends Controller
{ 
  public function index(Request $req) {

    //$search = $req->search;
    $search = request('search');

    if($search) {

      $events = Event::where([
        ['title','like','%'.$search.'%']
      ])->get();
    } else {
      $events = Event::all();
    }

    

    return view('welcome', ['events' => $events, 'search' => $search]);
  }

  public function create() {
    return view('events.create');
  }

  public function contato() {
    return view('events.contact');
  }

  public function store(Request $req) {
    $event = new Event;

    try {
      $event->title = $req->title;
      $event->date = $req->date;
      $event->city = $req->city;
      $event->private = $req->private;
      $event->description = $req->description;
      $event->items = $req->items;

      if($req->hasFile('image') && $req->file('image')->isValid()) {
        $reqImage = $req->image;
        $extension = $reqImage->extension();

        $imageName = md5($reqImage->getClientOriginalName() . strtotime("now")) . '.' . $extension;

        $req->image->move(public_path('img/events'), $imageName);
        $event->image = $imageName;

      }

      $user = auth()->user();
      $event->user_id = $user->id;    

      $event->save();
      $req->session()->flash('success', 'Evento salvo com sucesso!');

    } catch (\Exception $e) {
      $req->session()->flash('error', 'Ocorreu um problema!');

    }

    return redirect('/');
  }

  public function show($id) {
    $event = Event::findOrFail($id);

    $user = auth()->user();
    $hasUserJoined = false;
    
    if($user) {

      $userEvents = $user->eventsAsParticipant->toArray();

      foreach($userEvents as $userEvent) {
        if($userEvent['id'] == $id) {
          $hasUserJoined = true;
        }
      }
    }

    $eventOwner = User::where('id', $event->user_id)->first()->toArray();

    return view('events.show', ['event' => $event, 'eventOwner' => $eventOwner, 'hasUserJoined' => $hasUserJoined]);
  }

  public function dashboard() {
    $user = auth()->user();

    $events = $user->events;

    $eventsAsParticipant = $user->eventsAsParticipant;

    return view('events.dashboard', ['events' => $events, 'eventsAsParticipant' => $eventsAsParticipant]);  
  }

  public function destroy($id) {
    $event = Event::findOrFail($id)->delete();

    request()->session()->flash('success', 'Evento excluído com sucesso!');
    return redirect('/dashboard');
  }

  public function edit($id) {
    
    try {
      $user = auth()->user();

      $event = Event::findOrFail($id);

      if($user->id != $event->user_id) {
        request()->session()->flash('warning','Você não pode editar esse evento');
        return redirect('/dashboard');
      }

      return view('events.edit', ['event'=> $event]);

    } catch (\Exception $e) {

      request()->session()->flash('error', $e->getMessage());
    }
    
  }

  public function update(Request $req) {

    $data = $req->all();

    if($req->hasFile('image') && $req->file('image')->isValid()) {
      $reqImage = $req->image;
      $extension = $reqImage->extension();

      $imageName = md5($reqImage->getClientOriginalName() . strtotime("now")) . '.' . $extension;

      $req->image->move(public_path('img/events'), $imageName);
      $data['image'] = $imageName;

    }

    Event::findOrFail($req->id)->update($data);

    $req->session()->flash('success','Evento editado com sucesso!');

    return redirect('/dashboard');
  }

  public function joinEvent($id) {
    $user = auth()->user();

    $user->eventsAsParticipant()->attach($id);

    $event = Event::findOrFail($id);

    request()->session()->flash('success','Sua presença está confirmada no evento!');

    return redirect('/dashboard');
  }

  public function leaveEvent($id) {
    $user = auth()->user();

    $user->eventsAsParticipant()->detach($id);
    
    $event = Event::findOrFail($id);

    request()->session()->flash('info','Você saiu do evento: ' . $event->title);

    return redirect('/dashboard');
  }

}
