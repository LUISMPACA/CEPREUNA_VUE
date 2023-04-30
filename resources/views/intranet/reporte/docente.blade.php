@extends('layouts.app')
@section('titulo', 'Reporte | Docente')

@section('content')

    <i-reporte-docente :permissions="{{ $permisos }}"></i-reporte-docente>


@endsection

@section('scripts')

@endsection
