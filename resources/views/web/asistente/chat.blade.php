@extends('layouts.web')

@section('titulo', 'Asistente | Cepreuna')

@section('css')
    <style type="text/css">
        .vs--searchable .vs__dropdown-toggle {
            cursor: text;
            height: 36px;
        }
        .chat-box {
            max-height: 400px;
            overflow-y: scroll;
            padding: 15px;
            background-color: #f8f9fa;
            border-radius: 10px;
            box-shadow: inset 0 0 10px rgba(0, 0, 0, 0.1);
            display: flex;
            flex-direction: column;
        }
        .message {
            margin-bottom: 15px;
            border-radius: 5px;
            padding: 10px;
            max-width: 75%;
        }
        .message.user {
            background-color: #007bff;
            color: white;
            align-self: flex-start;
            border-top-right-radius: 0;
        }
        .message.assistant {
            background-color: #e9ecef;
            color: #333;
            align-self: flex-end;
            border-top-left-radius: 0;
        }
        .input-group {
            box-shadow: 0 0 10px rgba(0, 0, 0, 0.1);
            border-radius: 10px;
        }
        .btn-primary {
            border-top-left-radius: 0;
            border-bottom-left-radius: 0;
        }
        .breadcrumb {
            background-color: transparent;
            padding: 0;
            margin-bottom: 0;
        }
        .typing-indicator {
            display: none;
            align-self: flex-end;
            margin-bottom: 15px;
        }
        .typing-indicator .dot {
            display: inline-block;
            width: 8px;
            height: 8px;
            margin: 0 2px;
            background-color: #333;
            border-radius: 50%;
            animation: blink 1.4s infinite both;
        }
        .typing-indicator .dot:nth-child(1) {
            animation-delay: 0.2s;
        }
        .typing-indicator .dot:nth-child(2) {
            animation-delay: 0.4s;
        }
        .typing-indicator .dot:nth-child(3) {
            animation-delay: 0.6s;
        }

        @keyframes blink {
            0%, 80%, 100% {
                opacity: 0;
            }
            40% {
                opacity: 1;
            }
        }
    </style>
@endsection

@section('content')
<div id="app" class="bg-gray2">
    <div class="container">
        <div class="row">
            <div class="col-12">
                <div class="card shadow p-3 mb-5 bg-white rounded formulario">
                    <div class="card-body">
                        <div class="row">
                            <div class="col-md-12 col-xs-12">
                                <div class="float-right">
                                    <nav class="breadcrumb">
                                        <a class="breadcrumb-item" href="{{ url('/') }}">Inicio</a>
                                        <span class="breadcrumb-item active">Asistente</span>
                                        <span class="breadcrumb-item active">Estudiantes</span>
                                    </nav>
                                </div>
                                <h5 class="mt-3">ASISTENTE CEPREUNA 🤖</h5>
                                <hr>
                                <div id="chat-box" class="chat-box border p-3">
                                    <!-- Mensajes del chat se agregan aquí -->
                                </div>
                                <div id="typing-indicator" class="typing-indicator">
                                    <span class="dot"></span>
                                    <span class="dot"></span>
                                    <span class="dot"></span>
                                </div>
                                <form id="chat-form">
                                    <div class="input-group mt-3">
                                        <input type="text" id="content" name="content" class="form-control" placeholder="Escribe tu mensaje..." required>
                                        <input type="hidden" id="dni" name="dni" value="{{ $estudiante['nro_documento'] }}">
                                        <button type="submit" class="btn btn-primary">Enviar</button>
                                    </div>
                                </form>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>
@endsection

@section('scripts')
<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0-alpha1/dist/js/bootstrap.bundle.min.js"></script>
<script src="https://code.jquery.com/jquery-3.6.0.min.js"></script>
<script>
    $(document).ready(function() {
    $('#chat-form').on('submit', function(e) {
        e.preventDefault();

        var message = $('#content').val();
        var dni = $('#dni').val();
        var $button = $(this).find('button[type="submit"]');
        var $chatBox = $('#chat-box');
        var $typingIndicator = $('#typing-indicator');

        // Deshabilitar el botón de envío
        $button.prop('disabled', true);

        // Mostrar mensaje del usuario
        $chatBox.append('<div class="message user">' + message + '</div>');

        // Mostrar indicador de "escribiendo..."
        $typingIndicator.show();

        // Desplazar hacia abajo
        $chatBox.scrollTop($chatBox[0].scrollHeight);

        // Enviar mensaje al servidor
        $.ajax({
            url: '/api/generate-text',
            method: 'POST',
            data: {
                content: message,
                dni: dni,
                _token: '{{ csrf_token() }}'
            },
            success: function(response) {
                // Mostrar respuesta del asistente
                if (response.messages && response.messages.data) {
                    var firstMessage = response.messages.data[0].content[0].text.value;
                        $chatBox.append('<div class="message assistant"><strong>Asistente:</strong> ' + firstMessage  + '</div>');

                } else {
                    $chatBox.append('<div class="message assistant"><strong>Asistente:</strong> Error en la respuesta del asistente.</div>');
                }

                // Ocultar indicador de "escribiendo..."
                $typingIndicator.hide();

                // Limpiar el formulario
                $('#content').val('');

                // Desplazar hacia abajo
                $chatBox.scrollTop($chatBox[0].scrollHeight);

                // Habilitar el botón de envío
                $button.prop('disabled', false);
            },
            error: function(error) {
                console.error('Error:', error);
                alert('Hubo un error al procesar la solicitud.');

                // Ocultar indicador de "escribiendo..."
                $typingIndicator.hide();

                // Habilitar el botón de envío
                $button.prop('disabled', false);
            }
        });
    });
});

</script>
@endsection
