@extends('layouts.app')
@section('titulo', 'Administración | Ingresar Ingresantes')

@section('content')
@if (session('success'))
    <div class="alert alert-success">
        {{ session('success') }}
    </div>
@endif
@if ($errors->any())
    <div class="alert alert-danger">
        <ul>
            @foreach ($errors->all() as $error)
                <li>{{ $error }}</li>
            @endforeach
        </ul>
    </div>
@endif
@if (session('error'))
    <div class="alert alert-danger">
        {{ session('error') }}
    </div>
@endif
<div class="container mt-5">
        <div class="row justify-content-center">
            <div class="col-lg-6">
                <div class="card">
                    <div class="card-header bg-primary text-white">
                        <h3 class="m-0">Importar Datos desde Excel</h3>
                    </div>
                    <div class="card-body">
                        <form action="{{ route('importar.ingresantes') }}" method="post" enctype="multipart/form-data">
                            @csrf
                            <div class="form-group">
                                <label for="archivo_excel">Seleccionar archivo Excel:</label>
                                <input type="file" class="form-control-file" name="archivo_excel" id="archivo_excel">
                            </div>
                            <div class="text-center">
                                <button type="submit" class="btn btn-primary">Importar</button>
                            </div>
                        </form>
                    </div>
                </div>
            </div>
        </div>
    </div>
@endsection

@section('scripts')
        
@endsection
