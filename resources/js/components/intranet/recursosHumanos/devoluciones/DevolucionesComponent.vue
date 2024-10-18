<template>
  <div>
    <div class="row">
      <div class="col-sm-12">
        <div class="card">
          <header class="card-header">
            Devoluciones
            <button class="btn btn-primary float-right" @click="nuevo"><i class="fa fa-plus"></i> Nuevo</button>
          </header>
          <div class="card-body">
            <!-- <button type="button" id="agregar-area-users" class="btn btn-secondary btn-sm">
                      <i class="fa fa-plus"></i> Agregar
                      </button>  -->
            <v-server-table ref="table" :columns="columns" :options="options"
              url="/intranet/devolucion/devoluciones/lista/data">
              <div slot="actions" slot-scope="props">
                <!-- <a class="btn btn-sm btn-primary" href="#" >Detalles</a> -->
                <button class="btn btn-sm btn-info" @click="editar(props.row.id)">
                  Editar
                </button>
                <!-- <a href="#" @click="editar(props.row.id)"><i class="fa  fa-trash big-icon text-danger" aria-hidden="true"></i></a> -->
              </div>
            </v-server-table>
          </div>
        </div>
      </div>
    </div>
    <form @submit.prevent="submit">
      <div class="modal fade" id="ModalFormulario1" data-backdrop="static" data-keyboard="false" tabindex="-1"
        role="dialog" aria-labelledby="exampleModalLabel" aria-hidden="true">
        <div class="modal-dialog" role="document">
          <div class="modal-content">
            <div class="modal-header">
              <h5 class="modal-title" id="exampleModalLabel">
                {{ titulo }}
              </h5>
              <button type="button" class="close" data-dismiss="modal" aria-label="Close">
                <span aria-hidden="true">&times;</span>
              </button>
            </div>
            <div class="modal-body">
              <div class="container-fluid">
                <div class="row">
                  <div class="col-md-12 col-xs-12">
                    <div class="form-group">
                      <label for="nombres">Nombres</label>
                      <input type="text" class="form-control" name="nombres" id="nombres" v-model="fields.nombres" />
                      <div v-if="errors && errors.nombres" class="text-danger">
                        {{ errors.nombres[0] }}
                      </div>
                    </div>
                  </div>
                </div>

                <div class="row">
                  <div class="col-md-12 col-xs-12">
                    <div class="form-group">
                      <label for="nro_documento">Número de Documento</label>
                      <input type="text" class="form-control" name="nro_documento" id="nro_documento"
                        @input="changeDocumento" v-model="fields.nro_documento" />
                      <div v-if="errors && errors.nro_documento" class="text-danger">
                        {{ errors.nro_documento[0] }}
                      </div>
                    </div>
                  </div>
                </div>

                <div class="row">
                  <div class="form-group col-12">
                    <label for="fecha">Fecha</label>
                    <input type="date" class="form-control" id="fecha" v-model="fields.fecha" />
                    <div v-if="errors && errors.fecha" class="text-danger">
                      {{ errors.fecha[0] }}
                    </div>
                  </div>
                </div>

                <div class="row">
                  <div class="col-md-12 col-xs-12">
                    <div class="form-group">
                      <label for="nro_registro">Número Registro</label>
                      <input type="text" class="form-control" name="nro_registro" id="nro_registro"
                        @input="changeDocumento" v-model="fields.nro_registro" />
                      <div v-if="errors && errors.nro_registro" class="text-danger">
                        {{ errors.nro_registro[0] }}
                      </div>
                    </div>
                  </div>
                </div>

                <div class="row form-group">
                  <label class="col-12">Estado Procedido</label>
                  <div class="col-md-4">
                    <div class="radio">
                      <label>
                        <input type="radio" v-model="fields.procede" :value="0" checked />
                        No
                      </label>
                    </div>
                  </div>
                  <div class="col-md-4">
                    <div class="radio">
                      <label>
                        <input type="radio" v-model="fields.procede" :value="1" />
                        Si
                      </label>
                    </div>
                  </div>
                  <div class="col-md-4">
                    <div class="radio">
                      <label>
                        <input type="radio" v-model="fields.procede" :value="2" />
                        En Tramite
                      </label>
                    </div>
                  </div>
                  <div v-if="errors && errors.procede" class="text-danger">
                    {{ errors.procede[0] }}
                  </div>
                </div>
                <h5>Validación</h5>
                <div v-if="errors && errors.tokens" class="text-danger">
                  {{ errors.tokens[0] }}
                </div>
                <div class="row">
                  <w-pago :documento="fields.nro_documento" @result="resultPago = $event"></w-pago>
                  <br />
                  <div class="container" style="margin: 14px;">
                    <div class="row">
                      <table class="table">
                        <tbody>
                          <tr v-for="result in resultPago.pago" :key="result.secuencia">
                            <td>
                              <div class="alert alert-secondary" role="alert">
                                <b>Secuencia</b>: {{ result.secuencia }} | <b>Monto</b>: {{ result.monto }} | <b>
                                  fecha</b>:
                                {{ dateFormat(result.fecha) }}
                              </div>
                            </td>
                            <td>
                              <div class="alert alert-success" role="alert">
                                {{ result.message }}
                              </div>
                            </td>
                          </tr>
                        </tbody>
                      </table>
                    </div>
                  </div>
                </div>
              </div>
            </div>
            <div class="modal-footer">
              <button type="button" class="btn btn-secondary" data-dismiss="modal">
                Cerrar
              </button>
              <button type="submit" class="btn btn-primary">
                Guardar
              </button>
            </div>
          </div>
        </div>
      </div>
    </form>
  </div>
</template>

<script>
import axios from "axios";
import $ from "jquery";
import toastr from "toastr";
import "vue-select/dist/vue-select.css";

export default {
  // props:[],
  data() {
    return {
      ///table//
      edit: 0,
      id: 0,
      titulo: "",
      fields: {
        nombres: "",
        nro_documento: "",
        fecha: "",
        nro_registro: "",
        procede: "0",
        tokens: [],
      },
      errors: {},
      departamentos: [],
      provincias: [],
      distritos: [],
      disabledProvincia: true,
      disabledDistrito: true,
      distrito: "",
      columns: ["id", "nombres", "nro_documento", "fecha", "nro_registro", "procede", "pagos_id", "actions"],
      options: {
        headings: {
          id: "id",
          nombres: "Nombres",
          nro_documento: "Nro Documento",
          fecha: "Fecha",
          nro_registro: "Nro Registro",
          procede: "Estado Procedido",
          pagos_id: "Pagos",
          actions: "Acciones"
        },
        sortable: ["id", "fecha", "nro_registro", "procede"]
        // filterable: ['correlativo','num_mat','paterno'],
        // customFilters: ['correlativo','num_mat']
        // filterByColumn:true
      },
      resultPago: {
        pago: []
      },
      montoPagar: 0,
    };
  },

  methods: {
    nuevo: function () {
      this.edit = 0;
      this.errors = {};
      this.titulo = "Nueva Fecha de Inscripción";

      this.fields.nombres = "";
      this.fields.nro_documento = "";
      this.fields.fecha = "";
      this.fields.nro_registro = "";
      this.fields.procede = "";
      this.fields.tokens = [];

      $("#ModalFormulario1").modal("show");
    },
    editar: function (id) {
      this.edit = 1;
      this.id = id;
      this.errors = {};
      this.titulo = "Editar Fecha de Inscripción";
      axios.get("inscripciones/" + id + "/edit").then(response => {
        this.fields.estado = response.data.estado;
        this.fields.inicio = response.data.inicio;
        this.fields.fin = response.data.fin;
        this.fields.tipo_inscripcion = response.data.tipo_inscripcion;
        this.fields.tipo_usuario = response.data.tipo_usuario;
        this.fields.observacion = response.data.observacion;
      });

      $("#ModalFormulario1").modal("show");
    },
    submit: function () {
      this.errors = {};
      // Crear el array de tokens como parte de los campos que se van a enviar
      let tokens = [];
      this.resultPago.pago.map(function (pago) {
          tokens.push(typeof pago.token !== "undefined" ? pago.token : "");
      });

      // Agregar el array de tokens al objeto fields (o puedes crear otro objeto)
      if (tokens.length == 0) {
          this.fields.tokens = [""];  // Si no hay tokens, enviar un array vacío
      } else {
          this.fields.tokens = tokens;  // Si hay tokens, los agregamos al objeto fields
      }

      if (this.edit == 0)
        axios
          .post("devoluciones", this.fields)
          .then(response => {
            // $(".loader").hide();
            if (response.data.status) {
              this.$refs.table.refresh();
              toastr.success(response.data.message);
              $("#ModalFormulario1").modal("hide");
              // window.location.replace(response.data.url);
            } else {
              toastr.warning(response.data.message, "Aviso");
            }
          })
          .catch(error => {
            // $(".loader").hide();
            if (error.response.status === 422) {
              this.errors = error.response.data.errors || {};
            }
          });
      else {
        axios
          .put("inscripciones/" + this.id, this.fields)
          .then(response => {
            // $(".loader").hide();
            if (response.data.status) {
              this.$refs.table.refresh();
              toastr.success(response.data.message);
              $("#ModalFormulario1").modal("hide");
              // window.location.replace(response.data.url);
            } else {
              toastr.warning(response.data.message, "Aviso");
            }
          })
          .catch(error => {
            // $(".loader").hide();
            if (error.response.status === 422) {
              this.errors = error.response.data.errors || {};
            }
          });
      }
      // console.log("hols");
    },
    changeDocumento: function () {
      if (this.fields.nro_documento.length == 0) {
        this.statusDocumento = false;
      } else {
        this.statusDocumento = true;
      }
    },
    dateFormat: function(date) {
      return moment(date, "YYYY-MM-DD").format("DD-MM-YYYY");
    },
  },
  mounted() { }
};
</script>

<style></style>
