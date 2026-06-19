<?php

namespace App\Http\Controllers;

use App\Models\Cliente;
use App\Models\User;
use App\Http\Requests\StoreClienteRequest;
use App\Http\Requests\UpdateClienteRequest;
use App\Exports\ClientesExport;
use Maatwebsite\Excel\Facades\Excel;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage; // ✅ IMPORTANTE: Agrega esto

class ClienteController extends Controller
{
    public function index()
    {
        $clientes = Cliente::with('registrador')->get();
        return view('clientes.index', compact('clientes'));
    }

    public function create()
    {
        return view('clientes.create');
    }

    public function store(StoreClienteRequest $request)
    {
        $validatedData = $request->validated();
        
        // ✅ GUARDAR IMAGEN CORRECTAMENTE
        if ($request->hasFile('imagen') && $request->file('imagen')->isValid()) {
            $imagen = $request->file('imagen');
            $nombreImagen = time() . '_' . $imagen->getClientOriginalName();
            
            // Guardar en storage/app/public/img/clientes
            $path = $imagen->storeAs('public/img/clientes', $nombreImagen);
            
            // Guardar la ruta para acceder desde la web
            $validatedData['imagen'] = '/storage/img/clientes/' . $nombreImagen;
        }
        
        Cliente::create([
            'nombre' => $validatedData['nombre'],
            'documento' => $validatedData['documento'],
            'direccion' => $validatedData['direccion'],
            'telefono' => $validatedData['telefono'],
            'email' => $validatedData['email'],
            'imagen' => $validatedData['imagen'] ?? null,
            'estado' => '1',
            'registradopor' => auth()->id(),
        ]);

        return redirect()->route('clientes.index')
            ->with('success', 'Cliente creado correctamente');
    }

    public function show($id)
    {
        $cliente = Cliente::with(['pedidos', 'registrador'])->findOrFail($id);
        return view('clientes.show', compact('cliente'));
    }

    public function edit($id)
    {
        $cliente = Cliente::findOrFail($id);
        return view('clientes.edit', compact('cliente'));
    }

    public function update(UpdateClienteRequest $request, $id)
    {
        $cliente = Cliente::findOrFail($id);
        $validatedData = $request->validated();
        
        if ($request->hasFile('imagen')) {
            // ✅ ELIMINAR IMAGEN VIEJA USANDO STORAGE
            if ($cliente->imagen) {
                // Obtener la ruta del storage desde la URL
                $oldPath = str_replace('/storage/', 'public/', $cliente->imagen);
                if (Storage::exists($oldPath)) {
                    Storage::delete($oldPath);
                }
            }
            
            // ✅ GUARDAR NUEVA IMAGEN
            $imagen = $request->file('imagen');
            $nombreImagen = time() . '_' . $imagen->getClientOriginalName();
            $imagen->storeAs('public/img/clientes', $nombreImagen);
            $validatedData['imagen'] = '/storage/img/clientes/' . $nombreImagen;
        }
        
        $cliente->update($validatedData);

        return redirect()->route('clientes.index')
            ->with('success', 'Cliente actualizado correctamente');
    }

    public function cambioEstado($id)
    {
        $cliente = Cliente::findOrFail($id);
        $cliente->estado = $cliente->estado == '1' ? '0' : '1';
        $cliente->save();

        return redirect()->route('clientes.index')
            ->with('success', 'Estado del cliente actualizado');
    }

    public function destroy($id)
    {
        $cliente = Cliente::findOrFail($id);
        
        if ($cliente->pedidos()->count() > 0) {
            return redirect()->route('clientes.index')
                ->with('error', 'No se puede eliminar el cliente porque tiene ' . $cliente->pedidos()->count() . ' pedido(s) asociado(s).');
        }
        
        // ✅ ELIMINAR IMAGEN USANDO STORAGE
        if ($cliente->imagen) {
            $oldPath = str_replace('/storage/', 'public/', $cliente->imagen);
            if (Storage::exists($oldPath)) {
                Storage::delete($oldPath);
            }
        }
        
        $cliente->delete();

        return redirect()->route('clientes.index')
            ->with('success', 'Cliente eliminado correctamente');
    }

    public function cambioestadocliente(Request $request)
    {
        $cliente = Cliente::find($request->id);
        if ($cliente) {
            $cliente->estado = $request->estado;
            $cliente->save();
            return response()->json(['success' => true]);
        }
        return response()->json(['success' => false]);
    }

    public function reporte()
    {
        $clientes = Cliente::all();
        return view('clientes.reporte', compact('clientes'));
    }

    public function exportarExcel()
    {
        return Excel::download(new ClientesExport, 'clientes.xlsx');
    }
}