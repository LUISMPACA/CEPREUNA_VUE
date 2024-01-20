@extends('layouts.app')
@section('titulo', 'Reporte | Generador de reportes')

@section('content')
   <div class="card">
    <div class="card-body">
        <h1>Editor de consultas SQL</h1>
        <div class="alert alert-warning">
          Nota: recuerde que el select solo puede soportar hasta <strong>26 columnas (A - Z)</strong>
          <hr>
          ejemplo: "select columna1 as A, ... columna26 as Z from table"
        </div>
        <div class="alert alert-danger alert-dismissible fade show d-none" role="alert" id="alertContainer">
          <span id="alertMsg">
            
          </span>
          <button type="button" class="close" data-dismiss="alert" aria-label="Close">
            <span aria-hidden="true">&times;</span>
          </button>
        </div>
        
        <label for="sql_query"><h3>Consulta SQL:</h3></label><br />
        <div id="sql_editor" class="ace-theme-monokai"></div>
        <button id="submit_button" class="btn btn-success"><i class="fas fa-file-excel"></i>Generar reporte</button>
    </div>
    
        
   </div>
    


@endsection

@push('scripts')
<script src="https://cdn.jsdelivr.net/npm/ace-builds@1.4.12/src-noconflict/ace.js"></script>
<script src="https://cdn.jsdelivr.net/npm/ace-builds@1.4.12/src-noconflict/ext-language_tools.js"></script>

<script>
    
      var editor = ace.edit("sql_editor");
      editor.setTheme("ace/theme/monokai");
      editor.resize();
      editor.getSession().setMode("ace/mode/sql");
      editor.setOptions({
        enableBasicAutocompletion: true,
        enableSnippets: true,
        enableLiveAutocompletion: true
      });
      // Agregar los snippets de SELECT, JOIN, GROUP BY y funciones SQL al editor
        var snippetManager = ace.require("ace/snippets").snippetManager;
        // snippetManager.register(snippets, "sql");
      
    // Habilitar la inserción de snippets con la tecla Tab y Enter
    editor.commands.addCommand({
      name: "expandSnippet",
      bindKey: { 
        win: "Tab", 
        mac: "Tab"
      },
      exec: function(editor) {
        var snippetManager = ace.require("ace/snippets").snippetManager;
        var hasSelection = editor.session.selection.isEmpty();
        var cursor = editor.getCursorPosition();
        var line = editor.session.getLine(cursor.row);
        var match = line.match(/[^a-zA-Z0-9_]([a-zA-Z0-9_]+)$/);

        if (hasSelection || !match) {
          editor.execCommand("indent");
        } else {
          var prefix = line.substring(0, match.index);
          var snippet = snippetManager.getSnippetForSelection(editor);
          if (snippet) {
            editor.insert(snippet.content.replace("$", prefix));
          } else {
            editor.execCommand("indent");
          }
        }
      }
    });

      var submitButton = document.getElementById("submit_button");
      submitButton.addEventListener("click", function () {
        var sqlQuery = editor.getValue().replace(/\n/g, " ");
        // Enviar el contenido del editor a un servicio
        enviarConsultaSQL(sqlQuery);
      });
      

      function enviarConsultaSQL(query) {
  // Aquí puedes realizar la lógica para enviar la consulta SQL al servidor
  console.log("Enviando consulta SQL:", query);


  // Construir la solicitud fetch
  fetch('/intranet/reporte/reportes_personalizados', {
    method: 'POST',
    headers: {
      'Content-Type': 'application/json',
      'X-CSRF-TOKEN': $('meta[name="csrf-token"]').attr('content')
    },
    body: JSON.stringify({ query: query })
  })
    .then(response => {
      if (response.ok) {
        return response.blob();
        console.log(response)
      }
      //  else {
      //   console.log(response.status)
      //   throw new Error(response.status);
      // }
      else {
      // Lanzar un error con el mensaje de error del servidor
      return response.text().then(function(errorMessage) {
        throw new Error('Error en el servidor: ' + errorMessage);
      });
    }
    })
    .then(blob => {
      // Crear un objeto URL para el blob
      const url = URL.createObjectURL(blob);

      // Crear un enlace de descarga oculto
      const a = document.createElement('a');
      a.style.display = 'none';
      a.href = url;
      a.download = 'archivo_excel.xlsx';
      document.body.appendChild(a);

      // Simular el clic en el enlace de descarga
      a.click();

      // Limpiar y revocar el objeto URL
      URL.revokeObjectURL(url);
      document.body.removeChild(a);
    })
    
    .catch(error => {
      // Aquí puedes manejar los errores de la solicitud
      console.error('Error en la solicitud:', error);
      // console.log(error_al)
      const alertMsg=document.getElementById('alertMsg');
      alertMsg.innerHTML=error;
    });
    // if(error){
      const alertContainer=document.getElementById('alertContainer');
      
    alertContainer.classList.remove('d-none'); // Mostrar el alert

    setTimeout(function() {
      alertContainer.classList.add('d-none'); // Ocultar el alert después de 3 segundos (3000 milisegundos)
    }, 10000);
    
  
}
      
    </script>

@endpush
