<?php

namespace App\Http\Controllers;

use App\Http\Requests\StoreTicketRequest;
use App\Http\Requests\UpdateTicketRequest;
use App\Models\Ticket;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;

use Illuminate\Http\Request;

use App\Models\User;
use App\Notifications\TicketUpdatedNotification;
use App\Notifications\TicketUpdateNotification;

class TicketController extends Controller
{
    /**
     * Display a listing of the resource.
     */
    public function index()
    {
        //? CODICE NUOVO FUNZIONANTE 
        // Recupera l'utente autenticato
        $user = auth()->user();

        // Recupera i biglietti dell'utente, ordinati per data decrescente se l'utente è un amministratore, altrimenti recupera i biglietti dell'utente corrente
        $tickets = $user->isAdmin ? Ticket::latest()->get() : $user->tickets;

        // Passa i biglietti alla vista
        return view('ticket.index', compact('tickets'));
    }

    /**
     * Show the form for creating a new resource.
     */
    // public function create()
    // {
    //     return view('ticket.create');
    // }
    public function create()
    {
        dd('Arrivato nel metodo create');
        // Visualizza la pagina di creazione del ticket
        return view('ticket.create');
    }

    /**
     * Store a newly created resource in storage.
     */
    public function store(StoreTicketRequest $request)
    {
        $validatedData = $request->validate([
            'title' => 'required|string|max:255',
            'description' => 'required|string',
            'attachment' => 'nullable|file',
        ]);

        $ticketData = [
            'title' => $validatedData['title'],
            'description' => $validatedData['description'],
            'user_id' => auth()->id(),
        ];

        if ($request->hasFile('attachment')) {
            $attachment = $request->file('attachment');
            $filename = Str::random(25) . '.' . $attachment->getClientOriginalExtension();
            $path = $attachment->storeAs('avatars', $filename, 'public');
            $ticketData['attachment'] = $path;
        }

        $ticket = Ticket::create($ticketData);

        if ($ticket) {
            return redirect()->route('ticket.create')->with('success', 'Ticket creato correttamente!');
        } else {
            return back()->withInput()->withErrors(['Errore durante la creazione del ticket']);
        }
    }


    /**
     * Display the specified resource.
     */
    public function show(Ticket $ticket)
    {
        return view('ticket.show', compact('ticket'));
    }

    /**
     * Show the form for editing the specified resource.
     */
    public function edit(Ticket $ticket)
    {
        return view('ticket.edit', compact('ticket'));
    }

    /**
     * Update the specified resource in storage.
     */
    public function update(UpdateTicketRequest $request, Ticket $ticket)
    {
        if ($request->has('status')) {
            $ticket->user->notify(new TicketUpdateNotification($ticket));
        }

        // Valida i dati inviati dal form
        $validatedData = $request->validate([
            'title' => 'required|string|max:255',
            'description' => 'required|string',
            'attachment' => 'nullable|file',
        ]);

        // Aggiorna i dati del ticket
        $ticket->update([
            'title' => $validatedData['title'],
            'description' => $validatedData['description'],
        ]);

        // Gestisci l'aggiornamento dell'allegato (se presente)
        if ($request->hasFile('attachment')) {
            $attachment = $request->file('attachment');
            $filename = Str::random(25) . '.' . $attachment->getClientOriginalExtension();
            $path = $attachment->storeAs('attachments', $filename, 'public');

            // Se esiste già un allegato, eliminalo
            if ($ticket->attachment) {
                Storage::delete($ticket->attachment);
            }

            // Aggiorna il percorso dell'allegato del ticket
            $ticket->update(['attachment' => $path]);
        }

        // Ridirigi alla pagina dei dettagli del ticket con un messaggio di successo
        return redirect()->route('ticket.show', $ticket->id)->with('success', 'Ticket aggiornato con successo!');
    }

    public function updateStatus(Request $request, Ticket $ticket)
    {
        // Valida i dati inviati dal form, se necessario
        $validatedData = $request->validate([
            'status' => 'nullable|string|in:open,resolved,rejected',
        ]);

        // Aggiorna lo status del ticket
        $ticket->update(['status' => $validatedData['status']]);

        // Ritorna una risposta o reindirizza
        return redirect()->route('ticket.show', $ticket->id)->with('success', 'Status del ticket aggiornato con successo!');
    }




    /**
     * Remove the specified resource from storage.
     */
    public function destroy(Ticket $ticket)
    {
        //
        // Aggiungi qui la logica per eliminare il ticket
        $ticket->delete();

        // Ritorna a una pagina di destinazione dopo l'eliminazione, ad esempio la lista dei ticket
        return redirect()->route('ticket.index')->with('danger', 'Ticket deleted successfully');
    }
}

