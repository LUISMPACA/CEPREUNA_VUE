@extends('layouts.app')
@section('titulo', 'Inscripciones | Horas Docente')

@section('content')
    <div class="row">
        <div class="col-sm-12">
            <div class="card">
                <div class="card-body">
                    <i-lista-devoluciones :users="{{ $users }}" :external_url="{{ $external_url }}"
                        :permissions="{{ $permisos }}">
                    </i-lista-devoluciones>
                </div>
            </div>
        </div>
    </div>

@endsection

@section('scripts')

@endsection
