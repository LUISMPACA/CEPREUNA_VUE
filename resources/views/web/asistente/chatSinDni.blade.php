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
        .message-container {
            display: flex;
            justify-content: space-between;
            gap: 20px;
            margin-bottom: 15px;
        } 
        .hidden-container {
            display: none;
        }
        .message {
            margin-bottom: 15px;
            border-radius: 5px;
            padding: 10px;
            max-width: 75%;
        }
        .message:hover {
            box-shadow: 0 0 10px rgba(0, 0, 0, 0.2);
            cursor: pointer;
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
                                    <button type="button" id="finish-button" class="btn btn-dark btn-finish mt-3">Continuar</button>
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
    var hasChosenResponse = true; // Inicia en true porque no hay respuestas la primera vez
    var isFirstInteraction = true; // Variable para rastrear si es la primera interacción
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
                if (response.messagesOpenIA && response.messagesLlamaIA) {
                    var openAsistente = response.messagesOpenIA;
                    var llamaAsistente = response.messagesLlamaIA;

                    var cleanOpenAsistente = cleanMessage(openAsistente);
                    var formattedOpenMessage = convertToLinks(cleanOpenAsistente);

                    var cleanLlamaAsistente = cleanMessage(llamaAsistente);
                    var formattedLlamaMessage = convertToLinks(cleanLlamaAsistente);

                    // Mostrar las respuestas en paralelo con IDs únicos
                    $chatBox.append(
                        '<div class="message-container">' +
                            '<div class="message assistant assistant-openai" id="openai-' + response.id + '" data-id="' + response.id + '" data-target=".assistant-openai">' +
                            '<strong>Respuesta 1:</strong> ' + formattedOpenMessage +
                            '</div>' +
                            '<div class="message assistant assistant-llama" id="llama-' + response.id + '" data-id="' + response.id + '" data-target=".assistant-llama">' +
                            '<strong>Respuesta 2:</strong> ' + formattedLlamaMessage +
                            '</div>' +
                        '</div>'
                    );

                    //$chatBox.append('<div class="message assistant"><strong>Asistente:</strong> ' + formattedOpenMessage  + '</div>');

                    hasChosenResponse = false;
                    isFirstInteraction = false; // Marca que la primera interacción ha pasado
                } else {
                    $chatBox.append('<div class="message assistant"><strong>Asistente:</strong> Error en la respuesta del asistente.</div>');
                }

                // Ocultar indicador de "escribiendo..."
                $typingIndicator.hide();

                // Limpiar el formulario
                $('#content').val('');
                //$('#content').prop('disabled', false);
                //$('#content').focus();
                // Volver a habilitar el botón de envío
                //$button.prop('disabled', false);

                toastr.warning(
                    'Para realizar otra pregunta, por favor selecciona una de las respuestas disponibles.',
                    'Atención'
                );
                // Desplazar hacia abajo
                $chatBox.scrollTop($chatBox[0].scrollHeight);

                // Mostrar el botón "Finalizar"
                $finishButton.show();
            },
            error: function(error) {
                toastr.error(error.responseJSON.error, "Hubo un Error");
                $typingIndicator.hide();
                $('#content').val('');
                $button.prop('disabled', false);
                $finishButton.hide();
            }
        });
    });

    // Manejar la selección de respuestas al hacer clic en toda el área del mensaje
    $(document).on('click', '.message.assistant', function() {
        if (hasChosenResponse) return; // No hacer nada si ya se ha elegido una respuesta
        var $finishButton = $('#finish-button');
        // Obtén el ID del elemento clicado
        var clickedId = $(this).attr('id'); // Ej. 'openai-1' o 'llama-1'
        var responseIndex = $(this).data('id'); // ID de respuesta para identificar el grupo
        var targetClass = $(this).data('target');
        var bestModelValue = 0;
        console.log(clickedId, "  --  ", responseIndex, "  --  ", targetClass)
        if (targetClass == ".assistant-openai"){
            bestModelValue = 0;
        }
        if (targetClass == ".assistant-llama"){
            bestModelValue = 1;
        }
        // Elimina el contenedor pero mantiene el contenido
        $('.message-container').contents().unwrap();

        // Oculta todos los elementos con la clase 'assistant' en el grupo actual
        $('.message[data-id="' + responseIndex + '"]').hide();

        // Muestra solo el elemento que coincide con el ID seleccionado
        $('#' + clickedId).show();

        // // Realizar la petición AJAX
        $.ajax({
            url: '/api/best-response/' + responseIndex,
            type: 'POST',
            data: {
                best_model: bestModelValue,
                _token: '{{ csrf_token() }}' // Asegúrate de incluir el token CSRF si usas Laravel
            },
            success: function(response) {
                // Marcar que se ha elegido una respuesta
                hasChosenResponse = true;
                toastr.success('Has seleccionado una respuesta. Puedes proceder con otra pregunta.', 'Selección realizada');
                $finishButton.hide();
            },
            error: function(xhr) {
                //alert('Error: ' + );
                toastr.Error(xhr.responseJSON.error, 'Error');
            }
        });
        // Habilitar el campo de entrada y el botón de envío
        $('#content').prop('disabled', false);
        $('#content').focus();
        $('button[type="submit"]').prop('disabled', false);
    });



    $('#finish-button').on('click', function() {
        // Aquí puedes manejar la lógica para finalizar la conversación
            toastr.info('Para continuar de le click a la Respuesta Correcta', 'POR FAVOR');
        });
    });




    // Detectar clic en el input de contenido
    $('#content').on('click', function(e) {
        // Verificar si el input está deshabilitado
        if ($(this).is(':disabled')) {
            e.preventDefault(); // Prevenir la acción predeterminada
            toastr.warning(
            'Para realizar otra pregunta, por favor selecciona una de las respuestas disponibles.',
            'Atención'
        );
        }
    });

    function convertToLinks(text) {
        // Expresión regular para encontrar URLs
        var urlRegex = /(((https?:\/\/)|(www\.))[^\s\)\(\u{1F600}-\u{1F64F}]+[^\s\)\(\.,;])/gu;
        return text.replace(urlRegex, function(url) {
            var hyperlink = url;
            // Añadir 'http://' si la URL no tiene esquema
            if (!hyperlink.match('^https?:\/\/')) {
                hyperlink = 'http://' + hyperlink;
            }
            // Devolver el enlace en formato HTML
            return '<a href="' + hyperlink + '" target="_blank">' + url + '</a>';
        }).replace(/\s*\)\s*|\s*\(\s*/g, ' '); // Eliminar paréntesis y ajustar espacios
    }

    // Función para convertir texto en enlaces
    function cleanMessage(message) {
        // Elimina los patrones específicos del mensaje
        return message.replace(/\\u3010.*?source.*?\\u3011/g, '');
    }
</script>
@endsection
