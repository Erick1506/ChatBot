<?php

namespace App\Http\Controllers;

use App\Models\Empresa;
use Illuminate\Http\Request;

class EmpresaController extends Controller
{
    /**
     * Display a listing of the resource.
     */
    public function index()
    {
        $empresas = Empresa::all();
        return response()->json($empresas);
    }

    /**
     * Store a newly created resource in storage.
     */
    public function store(Request $request)
    {
        $request->validate([
            'nit' => 'required|string|max:20|unique:empresas',
            'representante_legal' => 'required|string|max:255',
            'correo' => 'required|email|max:100',
            'telefono' => 'required|string|max:20',
            'direccion' => 'required|string|max:255',
            'Usuario' => 'required|string|max:90|unique:empresas',
            'Contraseña' => 'required|string|max:40'
        ]);

        $empresa = Empresa::create($request->all());

        return response()->json([
            'message' => 'Empresa creada exitosamente',
            'empresa' => $empresa
        ], 201);
    }

    /**
     * Display the specified resource.
     */
    public function show($id)
    {
        $empresa = Empresa::findOrFail($id);
        return response()->json($empresa);
    }

    /**
     * Update the specified resource in storage.
     */
    public function update(Request $request, $id)
    {
        $empresa = Empresa::findOrFail($id);

        $request->validate([
            'nit' => 'sometimes|required|string|max:20|unique:empresas,nit,' . $id,
            'representante_legal' => 'sometimes|required|string|max:255',
            'correo' => 'sometimes|required|email|max:100',
            'telefono' => 'sometimes|required|string|max:20',
            'direccion' => 'sometimes|required|string|max:255',
            'Usuario' => 'sometimes|required|string|max:90|unique:empresas,Usuario,' . $id,
            'Contraseña' => 'sometimes|required|string|max:40'
        ]);

        $empresa->update($request->all());

        return response()->json([
            'message' => 'Empresa actualizada exitosamente',
            'empresa' => $empresa
        ]);
    }

    /**
     * Remove the specified resource from storage.
     */
    public function destroy($id)
    {
        $empresa = Empresa::findOrFail($id);
        $empresa->delete();

        return response()->json([
            'message' => 'Empresa eliminada exitosamente'
        ]);
    }

    /**
     * Validar credenciales de empresa
     */
    public function validarCredenciales(Request $request)
    {
        $request->validate([
            'usuario' => 'required|string',
            'contraseña' => 'required|string'
        ]);

        $empresa = Empresa::buscarPorUsuario($request->usuario);

        if (!$empresa) {
            return response()->json([
                'error' => 'No tienes usuario registrado con nosotros. Por favor, regístrate y vuelve aquí.',
                'valido' => false
            ], 404);
        }

        if (!$empresa->verificarContraseña($request->contraseña)) {
            return response()->json([
                'error' => 'Contraseña incorrecta',
                'valido' => false
            ], 401);
        }

        return response()->json([
            'message' => 'Credenciales válidas',
            'valido' => true,
            'empresa' => $empresa
        ]);
    }
}