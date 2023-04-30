@extends('layouts.app')
@section('titulo', 'Reporte | Asistencia Docente')

@section('content')

    <i-reporte-asistencia-docente :permissions="{{ $permisos }}"></i-reporte-asistencia-docente>


@endsection

@section('scripts')

@endsection
