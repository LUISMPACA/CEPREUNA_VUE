@extends('layouts.web')

@section('titulo', 'Asistente | Cepreuna')

@section('css')
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/toastr.js/latest/toastr.css" integrity="sha512-3pIirOrwegjM6erE5gPSwkUzO+3cTjpnV9lexlNZqvupR64iZBnOOTiiLPb9M36zpMScbmUNIcHUqKD47M719g==" crossorigin="anonymous" referrerpolicy="no-referrer" />
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
        .btn-finish {
            width: 100%;
            padding: 10px;
            font-size: 16px;
            background-color: #007bff;
            color: white;
            border: none;
            border-radius: 5px;
            cursor: pointer;
            display: none; /* Ocultar el botón inicialmente */
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
                                <div id="chat-box" class="chat-box border p-3" style="min-height:50vh">
                                    <!-- Mensajes del chat se agregan aquí -->
                                </div>
                                <div id="typing-indicator" class="typing-indicator">
                                    <span class="dot"></span>
                                    <span class="dot"></span>
                                    <span class="dot"></span>
                                </div>
                                <form id="chat-form">
                                    <!-- Campo de DNI oculto -->
                                    <div class="form-group mx-sm-3 mb-2" style="display:none;">
                                        <input type="text" id="dni" name="dni" class="form-control form-control-sm" placeholder="DNI">
                                    </div>
                                    <div class="input-group mt-3">
                                        <input type="text" id="content" name="content" class="form-control" placeholder="Escribe tu mensaje..." required>
                                        <button type="submit" class="btn btn-primary">Enviar</button>
                                    </div>
                                    <button type="button" id="finish-button" class="btn btn-dark btn-finish mt-3">Finalizar</button>
                                </form>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

<!-- Modal para capturar el DNI -->
<div class="modal fade" id="dniModal" tabindex="-1" aria-labelledby="dniModalLabel" aria-hidden="true">
    <div class="modal-dialog">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title" id="dniModalLabel">Ingrese su DNI</h5>
            </div>
            <div class="modal-body">
                <div class="form-group">
                    <label for="dniInput">DNI</label>
                    <input type="number" min="8" class="form-control" id="dniInput" placeholder="Ingrese su DNI" required>
                </div>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-primary" id="saveDniBtn">Guardar</button>
            </div>
        </div>
    </div>
</div>
@endsection

@section('scripts')
<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0-alpha1/dist/js/bootstrap.bundle.min.js"></script>
<script src="https://code.jquery.com/jquery-3.6.0.min.js"></script>
<script src="https://cdnjs.cloudflare.com/ajax/libs/toastr.js/latest/toastr.min.js" integrity="sha512-VEd+nq25CkR676O+pLBnDW09R7VQX9Mdiij052gVCp5yVH3jGtH70Ho/UUv4mJDsEdTvqRCFZg0NKGiojGnUCw==" crossorigin="anonymous" referrerpolicy="no-referrer"></script>
<script>
    var conversationHistory = [];
    $(document).ready(function() {
        // Mostrar el modal al cargar la página
        $('#dniModal').modal('show');

        $('#saveDniBtn').on('click', function() {
            var dni = $('#dniInput').val();
            if (!dni || dni.trim() === "" || dni.length !== 8) {
                toastr.warning('El DNI es obligatorio');
                return;
            } else{
                $('#dni').val(dni);
                $('#dniModal').modal('hide');
            }
        });

        $('#chat-form').on('submit', function(e) {
            e.preventDefault();


            if ($('#dni').val() === "") {
                toastr.warning('Debe ingresar su DNI primero');
                $('#dniModal').modal('show');
                return;
            }

            var message = $('#content').val();
            var $button = $(this).find('button[type="submit"]');
            var $chatBox = $('#chat-box');
            var $typingIndicator = $('#typing-indicator');
            var $finishButton = $('#finish-button');

            // Deshabilitar el botón de envío
            $button.prop('disabled', true);
            $('#content').val("");
            $('#content').prop('disabled', true);

            // Mostrar mensaje del usuario
            $chatBox.append('<div class="message user">' + message + '</div>');

            // Mostrar indicador de "escribiendo..."
            $typingIndicator.show();

            // Desplazar hacia abajo
            $chatBox.scrollTop($chatBox[0].scrollHeight);

            conversationHistory.push({role: 'user', content: message});
            // Enviar mensaje al servidor
            $.ajax({
                url: '/api/generate-text',
                method: 'POST',
                data: {
                    content: message,
                    dni: $('#dni').val(),
                    conversation: conversationHistory,
                    _token: '{{ csrf_token() }}'
                },
                success: function(response) {
                    // Mostrar respuesta del asistente
                    if (response.messages && response.messages.data) {
                        var firstMessage = response.messages.data[0].content[0].text.value;
                        var firstMessage = cleanMessage(firstMessage);
                        var formattedMessage = convertToLinks(firstMessage);
                        

                        $chatBox.append('<div class="message assistant"><strong>Asistente:</strong> ' + formattedMessage  + '</div>');

                    } else {
                        $chatBox.append('<div class="message assistant"><strong>Asistente:</strong> Error en la respuesta del asistente.</div>');
                    }

                    // Ocultar indicador de "escribiendo..."
                    $typingIndicator.hide();

                    // Limpiar el formulario
                    $('#content').val('');
                    $('#content').prop('disabled', false);
                    $('#content').focus();
                    // Volver a habilitar el botón de envío
                    $button.prop('disabled', false);

                    // Desplazar hacia abajo
                    $chatBox.scrollTop($chatBox[0].scrollHeight);

                    // Mostrar el botón "Finalizar"
                    $finishButton.show();
                },
                error: function(error) {
                    // $chatBox.append('<div class="message assistant"><strong>Asistente:</strong> Error en el servidor. Inténtelo de nuevo más tarde.</div>');
                    // $typingIndicator.hide();
                    // $button.prop('disabled', false);
                    // $chatBox.scrollTop($chatBox[0].scrollHeight);
                    console.error('Error:', error);
                    toastr.error(error.responseJSON.error, "Hubo un Error");
                    // Ocultar indicador de "escribiendo..."
                    $typingIndicator.hide();
                    $('#content').val('');
                    // Habilitar el botón de envío
                    $button.prop('disabled', false);
                }
            });
        });

        $('#finish-button').on('click', function() {
            location.reload();
        });

        function convertToLinks(text) {
            var urlRegex = /(((https?:\/\/)|(www\.))[^\s]+)/g;
            return text.replace(urlRegex, function(url) {
                var hyperlink = url;
                if (!hyperlink.match('^https?:\/\/')) {
                    hyperlink = 'http://' + hyperlink;
                }
                return '<a href="' + hyperlink + '" target="_blank">' + url + '</a>';
            });
        }

        function cleanMessage(message) {
            // Elimina los patrones específicos del mensaje
            return message.replace(/\\u3010.*?source.*?\\u3011/g, '');
        }
    });
</script>
@endsection
