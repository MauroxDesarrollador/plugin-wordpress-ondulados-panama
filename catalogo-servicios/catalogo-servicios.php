<?php
/*
Plugin Name: Catálogo de Servicios
Description: Plugin para gestionar un catálogo de servicios con imágenes
Version: 2.8
Author: Mauricio Reyes
Author URI: https://mauricioreyesdev.com
License: GPL2
*/
define('CATALOGO_SERVICIOS_VERSION', '2.8'); // Cambia la versión según sea necesario
// Crear tabla al activar el plugin
register_activation_hook(__FILE__, 'crear_tabla_servicios');

function crear_tabla_servicios() {
    global $wpdb;
    $tabla = $wpdb->prefix . 'servicios';
    $charset = $wpdb->get_charset_collate();

    $sql = "CREATE TABLE $tabla (
        id mediumint(9) NOT NULL AUTO_INCREMENT,
        titulo varchar(255) NOT NULL,
        categoria varchar(100) NOT NULL,
        subcategoria varchar(100) DEFAULT NULL,
        imagen varchar(255) NOT NULL,
        fecha datetime DEFAULT CURRENT_TIMESTAMP NOT NULL,
        PRIMARY KEY  (id)
    ) $charset;";

    require_once(ABSPATH . 'wp-admin/includes/upgrade.php');
    dbDelta($sql);

    update_option('catalogo_servicios_version', CATALOGO_SERVICIOS_VERSION);

}

// Añadir menú de administración
add_action('admin_menu', 'menu_servicios');

function menu_servicios() {
    add_menu_page(
        'Catálogo de Servicios',
        'Catálogo de Servicios',
        'manage_options',
        'catalogo-servicios',
        'admin_servicios',
        'dashicons-portfolio',
        20
    );
}

add_action('admin_init', 'catalogo_servicios_actualizar');

function catalogo_servicios_actualizar() {
    global $wpdb;
    $tabla = $wpdb->prefix . 'servicios';

    // Obtener la versión actual almacenada
    $version_actual = get_option('catalogo_servicios_version');

    // Si la versión no coincide, realizar actualizaciones
    if ($version_actual !== CATALOGO_SERVICIOS_VERSION) {
        // Ejemplo: Agregar un nuevo campo "descripcion" si no existe
         // Asegurarse de que las columnas existen y tienen el tipo correcto
        $columnas = [
            'titulo' => "ALTER TABLE $tabla MODIFY COLUMN titulo varchar(255) NOT NULL",
            'categoria' => "ALTER TABLE $tabla MODIFY COLUMN categoria varchar(100) NOT NULL",
            'subcategoria' => "ALTER TABLE $tabla MODIFY COLUMN subcategoria varchar(100) DEFAULT NULL",
            'imagen' => "ALTER TABLE $tabla MODIFY COLUMN imagen varchar(255) NOT NULL",
            'fecha' => "ALTER TABLE $tabla MODIFY COLUMN fecha datetime DEFAULT CURRENT_TIMESTAMP NOT NULL"
        ];

        foreach ($columnas as $columna => $alter_sql) {
            $existe_columna = $wpdb->get_results($wpdb->prepare(
                "SHOW COLUMNS FROM $tabla LIKE %s",
                $columna
            ));
            if ($existe_columna) {
                $wpdb->query($alter_sql);
            }
        }

        // Actualizar la versión en la base de datos
        update_option('catalogo_servicios_version', CATALOGO_SERVICIOS_VERSION);
    }
}
// Interfaz de administración
function admin_servicios() {
    global $wpdb;
    $tabla = $wpdb->prefix . 'servicios';

    // Manejar eliminación de servicios
    if (isset($_GET['eliminar_servicio'])) {
        $id = intval($_GET['eliminar_servicio']);
        $wpdb->delete($tabla, ['id' => $id]);
        echo '<div class="notice notice-success"><p>Servicio eliminado!</p></div>';
    }

    // Manejar edición de servicios
    $servicio_a_editar = null;
    if (isset($_GET['editar_servicio'])) {
        $id = intval($_GET['editar_servicio']);
        $servicio_a_editar = $wpdb->get_row($wpdb->prepare("SELECT * FROM $tabla WHERE id = %d", $id));
    }

    if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['editar_servicio'])) {
        $id = intval($_POST['id']);
        $titulo = sanitize_text_field($_POST['titulo']);
        $categoria = sanitize_text_field($_POST['categoria']);
        $subcategoria = sanitize_text_field($_POST['subcategoria']);
        $imagen = esc_url_raw($_POST['imagen']);

        $resultado = $wpdb->update(
            $tabla,
            [
                'titulo' => $titulo,
                'categoria' => $categoria,
                'subcategoria' => $subcategoria,
                'imagen' => $imagen,
            ],
            ['id' => $id],
            ['%s', '%s', '%s', '%s'],
            ['%d']
        );

        if ($resultado !== false) {
            echo '<div class="notice notice-success"><p>Servicio actualizado correctamente!</p></div>';
            // Redirigir para mostrar el formulario de registro
            echo '<script>window.location.href="?page=catalogo-servicios";</script>';
            exit;
        } else {
            echo '<div class="notice notice-error"><p>Error al actualizar el servicio. Por favor, verifica los datos.</p></div>';
        }
    }

    // Decodificar JSON
    $categorias = [];

    // Procesar formulario
    if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['nuevo_servicio'])) {
        global $wpdb;
        $tabla = $wpdb->prefix . 'servicios';

        // Validar y sanitizar los datos del formulario
        $titulo = sanitize_text_field($_POST['titulo']);
        $categoria = sanitize_text_field($_POST['categoria']);
        $subcategoria = sanitize_text_field($_POST['subcategoria']);
        $imagen = esc_url_raw($_POST['imagen']);

        // Insertar los datos en la base de datos
        $resultado = $wpdb->insert(
            $tabla,
            [
                'titulo' => $titulo,
                'categoria' => $categoria,
                'subcategoria' => $subcategoria,
                'imagen' => $imagen,
            ],
            [
                '%s', // Tipo de dato: string
                '%s', // Tipo de dato: string
                '%s', // Tipo de dato: string
                '%s', // Tipo de dato: string
            ]
        );

        // Verificar si la inserción fue exitosa
        if ($resultado) {
            echo '<div class="notice notice-success"><p>Servicio agregado correctamente!</p></div>';
        } else {
            echo '<div class="notice notice-error"><p>Error al guardar el servicio. Por favor, verifica los datos.</p></div>';
        }
    }

    // Obtener servicios existentes
    $servicios = $wpdb->get_results("SELECT * FROM $tabla ORDER BY fecha DESC");

    // Mostrar interfaz
    ?>
    <div class="wrap">
        <div align="center" style="margin-bottom: 20px;text-align:center">
            <img src="https://mauricioreyesdev.com/wp-content/uploads/2025/04/logo-3fe76ad3-1.webp" style="width: 300px;margin-top: 2em;"/>
        </div>
        <button onclick="window.location.href='?page=catalogo-servicios'" class="button" style="float:right;margin-bottom: 20px; background-color: #e5257e; color: white; border: none; padding: 10px 20px; cursor: pointer; border-radius: 5px;">
            Crear nuevo servicio
        </button>
        <h1>Catálogo de Servicios</h1>

        <?php if (isset($servicio_a_editar)): ?>
        <h2>Editar Servicio</h2>
        <form method="post">
            <input type="hidden" name="id" value="<?= esc_attr($servicio_a_editar->id) ?>">
            <table class="form-table">
                <tr>
                    <th><label>Título</label></th>
                    <td><input type="text" name="titulo" value="<?= esc_attr($servicio_a_editar->titulo) ?>" required class="regular-text"></td>
                </tr>
                <tr>
                    <th><label>Categoría</label></th>
                    <td>
                        <select name="categoria" id="categoria_editar" class="regular-text" required>
                            <option value="">Seleccionar Categoría</option>
                            <?php foreach (array_keys($categorias) as $categoria): ?>
                                <option value="<?= esc_attr($categoria) ?>" <?= selected($categoria, $servicio_a_editar->categoria, false) ?>><?= esc_html($categoria) ?></option>
                            <?php endforeach; ?>
                        </select>
                    </td>
                </tr>
                <tr>
                    <th><label>Subcategoría</label></th>
                    <td>
                        <select name="subcategoria" id="subcategoria_editar" class="regular-text">
                            <option value="">Seleccionar Subcategoría</option>
                            <?php if (!empty($categorias[$servicio_a_editar->categoria])): ?>
                                <?php foreach ($categorias[$servicio_a_editar->categoria] as $subcategoria): ?>
                                    <option value="<?= esc_attr($subcategoria) ?>" <?= selected($subcategoria, $servicio_a_editar->subcategoria, false) ?>><?= esc_html($subcategoria) ?></option>
                                <?php endforeach; ?>
                            <?php endif; ?>
                        </select>
                    </td>
                </tr>
                <tr>
                    <th><label>Imagen</label></th>
                    <td>
                        <input type="text" name="imagen" id="imagen_url_editar" value="<?= esc_url($servicio_a_editar->imagen) ?>" class="regular-text">
                        <input type="button" name="upload-btn-editar" id="upload-btn-editar" class="button-secondary" value="Subir Imagen">
                    </td>
                </tr>
            </table>
            <?php submit_button('Actualizar Servicio', 'primary', 'editar_servicio') ?>
        </form>
        <?php endif; ?>

        <?php if (isset($servicio_a_editar)==false): ?>
        <h2>Añadir Nuevo Servicio</h2>
        <form method="post">
            <table class="form-table">
                <tr>
                    <th><label>Título</label></th>
                    <td><input type="text" name="titulo" required class="regular-text"></td>
                </tr>
                <tr>
                    <th><label>Categoría</label></th>
                    <td>
                        <select name="categoria" id="categoria" class="regular-text" required>
                            <option value="">Seleccionar Categoría</option>
                        </select>
                    </td>
                </tr>
                <tr>
                    <th><label>Subcategoría</label></th>
                    <td>
                        <select name="subcategoria" id="subcategoria" class="regular-text">
                            <option value="">Seleccionar Subcategoría</option>
                        </select>
                    </td>
                </tr>
                <tr>
                    <th><label>Imagen</label></th>
                    <td>
                        <input type="text" name="imagen" id="imagen_url" class="regular-text">
                        <input type="button" name="upload-btn" id="upload-btn" class="button-secondary" value="Subir Imagen">
                    </td>
                </tr>
            </table>
            <?php submit_button('Guardar Servicio', 'primary', 'nuevo_servicio') ?>
        </form>
        <?php endif; ?>

        <h2>Servicios Registrados</h2>
        <table class="wp-list-table widefat fixed striped">
            <thead>
                <tr>
                    <th>ID</th>
                    <th>Título</th>
                    <th>Categoría</th>
                    <th>Subcategoría</th>
                    <th>Imagen</th>
                    <th>Fecha</th>
                    <th>Acciones</th>
                </tr>
            </thead>
            <tbody>
                <?php foreach ($servicios as $servicio): ?>
                <tr>
                    <td><?= $servicio->id ?></td>
                    <td><?= esc_html($servicio->titulo) ?></td>
                    <td><?= esc_html($servicio->categoria) ?></td>
                    <td><?= esc_html($servicio->subcategoria) ?></td>
                    <td><img src="<?= esc_url($servicio->imagen) ?>" style="max-width: 100px; height: auto;"></td>
                    <td><?= $servicio->fecha ?></td>
                    <td>
                        <a href="?page=catalogo-servicios&editar_servicio=<?= $servicio->id ?>" class="button">Editar</a>
                        <a href="?page=catalogo-servicios&eliminar_servicio=<?= $servicio->id ?>" class="button button-danger" onclick="return confirm('¿Estás seguro de eliminar este servicio?')">Eliminar</a>
                    </td>
                </tr>
                <?php endforeach; ?>
            </tbody>
        </table>

       
    </div>

    <script>
        document.addEventListener('DOMContentLoaded', function () {
            // Definir las categorías y subcategorías
            const categorias = {
            "Paquete de servicios" : [
                "Rescata tu Melena",
                "Mima tu Melena Chiquita",
                "Empecemos desde 0",
                "Engalana tu Melena natural",
                "Curly Men Pack"
            ],
            "Cortes de cabello" : [
                "Capas cortas",
                "Capas largas",
                "Fade Plus",
                "Fade Plus con diseño",
                "Corte con máquina standard",
                "Corte con máquina standard con diseño",
                "Pixie",
                "Curly Bob asimétrico",
                "Curly Bob francés",
                "Shaggy",
                "Flequillo"
            ],
            "Trenzas" : [],
            "Color e iluminaciones" : [
                "Color uniforme",
                "Cobertura de canas",
                "Mechas creativas",
                "Balayage"
            ],
            "Peinados para eventos" : [],
            "Tratamiento intenso de uso profesional" : [],
            "Rizado permanente en cabello liso" : [],
            "Patrón de cabello" : [],
            "Hidratación profunda de uso profesional" : [
                "Células Madres - Alfaparf",
                "Restructure Morphosis - Framesi",
                "Fusio Dose - Kérastase"
            ]
        };

            const categoriaSelect = document.getElementById('categoria') || document.getElementById('categoria_editar');
            const subcategoriaSelect = document.getElementById('subcategoria') || document.getElementById('categoria_editar');;
            
            for (const categoria in categorias) {
                const option = document.createElement('option');
                option.value = categoria;
                option.textContent = categoria;
                categoriaSelect.appendChild(option);
            }
            // Evento para cargar subcategorías al cambiar la categoría
            categoriaSelect.addEventListener('change', function () {
                const categoriaSeleccionada = this.value;

                // Limpiar subcategorías
                subcategoriaSelect.innerHTML = '<option value="">Seleccionar Subcategoría</option>';

                // Cargar subcategorías correspondientes
                if (categorias[categoriaSeleccionada]) {
                    for (const subcategoria of categorias[categoriaSeleccionada]) {
                        const option = document.createElement('option');
                        option.value = subcategoria;
                        option.textContent = subcategoria;
                        subcategoriaSelect.appendChild(option);
                    }
                }
            });

            // Cargar categorías y subcategorías en el formulario de edición
            if (document.getElementById('categoria_editar')) {
                const categoriaEditarSelect = document.getElementById('categoria_editar');
                const subcategoriaEditarSelect = document.getElementById('subcategoria_editar');

                for (const categoria in categorias) {
                    const option = document.createElement('option');
                    option.value = categoria;
                    option.textContent = categoria;
                    categoriaEditarSelect.appendChild(option);
                }

                categoriaEditarSelect.value = "<?= esc_js($servicio_a_editar->categoria) ?>";

                // Cargar subcategorías correspondientes
                if (categorias[categoriaEditarSelect.value]) {
                    for (const subcategoria of categorias[categoriaEditarSelect.value]) {
                        const option = document.createElement('option');
                        option.value = subcategoria;
                        option.textContent = subcategoria;
                        subcategoriaEditarSelect.appendChild(option);
                    }
                }

                subcategoriaEditarSelect.value = "<?= esc_js($servicio_a_editar->subcategoria) ?>";

                // Evento para cargar subcategorías al cambiar la categoría en el formulario de edición
                categoriaEditarSelect.addEventListener('change', function () {
                    const categoriaSeleccionada = this.value;

                    // Limpiar subcategorías
                    subcategoriaEditarSelect.innerHTML = '<option value="">Seleccionar Subcategoría</option>';

                    // Cargar subcategorías correspondientes
                    if (categorias[categoriaSeleccionada]) {
                        for (const subcategoria of categorias[categoriaSeleccionada]) {
                            const option = document.createElement('option');
                            option.value = subcategoria;
                            option.textContent = subcategoria;
                            subcategoriaEditarSelect.appendChild(option);
                        }
                    }
                });
            }
        });

        jQuery(document).ready(function($){
            $('#upload-btn').click(function(e) {
                e.preventDefault();
                var image = wp.media({ 
                    title: 'Seleccionar Imagen',
                    multiple: false
                }).open()
                .on('select', function(e){
                    var uploaded_image = image.state().get('selection').first();
                    var image_url = uploaded_image.toJSON().url;
                    $('#imagen_url').val(image_url);
                });
            });

            $('#upload-btn-editar').click(function(e) {
                e.preventDefault();
                var image = wp.media({ 
                    title: 'Seleccionar Imagen',
                    multiple: false
                }).open()
                .on('select', function(e){
                    var uploaded_image = image.state().get('selection').first();
                    var image_url = uploaded_image.toJSON().url;
                    $('#imagen_url_editar').val(image_url);
                });
            });
        });
    </script>
    <?php
}

// Cargar scripts necesarios para el uploader
add_action('admin_enqueue_scripts', 'cargar_scripts_servicios');

function cargar_scripts_servicios($hook) {
    if ('toplevel_page_catalogo-servicios' !== $hook) return;

    wp_enqueue_media();
    wp_enqueue_script('jquery');

}

// Shortcode para mostrar servicios con paginación
add_shortcode('mostrar_servicios', 'mostrar_servicios_shortcode');

function mostrar_servicios_shortcode($atts) {
    global $wpdb;

    // Configuración de paginación
    $tabla = $wpdb->prefix . 'servicios';
    $por_pagina = 5; // Número de servicios por página
    $pagina_actual = isset($_GET['pagina']) ? max(1, intval($_GET['pagina'])) : 1;
    $offset = ($pagina_actual - 1) * $por_pagina;

    // Filtros
    $categoria_filtro = isset($_GET['categoria']) ? sanitize_text_field($_GET['categoria']) : '';
    $titulo_filtro = isset($_GET['titulo']) ? sanitize_text_field($_GET['titulo']) : '';
    $fecha_inicio = isset($_GET['fecha_inicio']) ? sanitize_text_field($_GET['fecha_inicio']) : '';
    $fecha_fin = isset($_GET['fecha_fin']) ? sanitize_text_field($_GET['fecha_fin']) : '';

    // Ajustar fecha_fin para incluir el final del día

    // Construir consulta con filtros
    $query = "SELECT * FROM $tabla WHERE 1=1";
    $query_params = [];

    if (!empty($categoria_filtro)) {
        $query .= " AND categoria = %s";
        $query_params[] = $categoria_filtro;
    }

    if (!empty($titulo_filtro)) {
        $query .= " AND titulo LIKE %s";
        $query_params[] = '%' . $wpdb->esc_like($titulo_filtro) . '%';
    }

    if (!empty($fecha_inicio)) {
        $query .= " AND fecha >= %s";
        $query_params[] = $fecha_inicio;
    }

    if (!empty($fecha_fin)) {
        $query .= " AND fecha >= %s";
        $query_params[] = $fecha_fin . ' 23:59:59'; // Ajustar fecha_fin para incluir el final del día
    }

    $query .= " ORDER BY fecha DESC LIMIT %d OFFSET %d";
    $query_params[] = $por_pagina;
    $query_params[] = $offset;

    $servicios = $wpdb->get_results($wpdb->prepare($query, $query_params));

    // Contar total de servicios
    $total_servicios_query = "SELECT COUNT(*) FROM $tabla WHERE 1=1";
    $total_params = [];

    if (!empty($categoria_filtro)) {
        $total_servicios_query .= " AND categoria = %s";
        $total_params[] = $categoria_filtro;
    }

    if (!empty($titulo_filtro)) {
        $total_servicios_query .= " AND titulo LIKE %s";
        $total_params[] = '%' . $wpdb->esc_like($titulo_filtro) . '%';
    }

    if (!empty($fecha_inicio)) {
        $total_servicios_query .= " AND fecha >= %s";
        $total_params[] = $fecha_inicio;
    }

    if (!empty($fecha_fin)) {
        // Ajustar fecha_fin para incluir el final del día
        $total_servicios_query .= " AND fecha <= %s";
        $total_params[] = $fecha_fin;
    }

    $total_servicios = $wpdb->get_var($wpdb->prepare($total_servicios_query, $total_params));
    $total_paginas = ceil($total_servicios / $por_pagina);

    // Generar HTML
    ob_start();
    ?>
    <div class="servicios-lista">
    <p style="color: rgb(39, 171, 114); text-align: center; width: 100%; font-size: 20px; font-weight: 700;" class="">Si encuentras tu resultado, comparte tu foto y menciónanos en Instagram como @ondulados.pa</p>
        <!-- Buscador por categoría -->
        <form method="get" class="buscador-filtros" style="display: flex; flex-wrap: wrap; justify-content: center; align-items: center; gap: 10px; margin-bottom: 20px;">
           
            <div style="position: relative; flex: 1; min-width: 200px;">
                 <label>Título:</label>
                 <input style="height: 45px; width: 100%;" type="text" name="titulo" placeholder="Buscar por título" value="<?= esc_attr($titulo_filtro) ?>">
           
            </div>
            <div style="flex: 1; min-width: 200px;">
                 <label>Fecha inicio:</label>
                 <input style="height: 45px; width: 100%;" type="date" name="fecha_inicio" placeholder="Fecha inicio" value="<?= esc_attr($fecha_inicio) ?>">
            
            </div>
          
            <div style="flex: 1; min-width: 200px;">
                <label>Fecha fin:</label>
                <input style="height: 45px; width: 100%;" type="date" name="fecha_fin" placeholder="Fecha fin" value="<?= esc_attr($fecha_fin) ?>">
            </div>
            <button type="submit" class="button" style="height: 45px; background-color:#e6287e; color: white; border: none; padding: 0 20px; cursor: pointer; border-radius: 5px; display: flex; align-items: center; gap: 5px;margin-top: 20px;">
                Buscar
            </button>
        </form>
        <div id="botones_categorias" style="text-align:center"></div>
        <!-- Mostrar servicios en formato de cards -->
        <div class="cards-container">
            <?php if ($servicios): ?>
                <?php foreach ($servicios as $servicio): ?>
                    <div class="card">
                        <img src="<?= esc_url($servicio->imagen) ?>" alt="<?= esc_attr($servicio->titulo) ?>" class="card-image">
                        <h3 class="card-title"><?= esc_html($servicio->titulo) ?></h3>
                        <button class="button compartir-btn" data-title="<?= esc_attr($servicio->titulo) ?>" data-image="<?= esc_url($servicio->imagen) ?>">
                            <img src="https://mauricioreyesdev.com/wp-content/uploads/2025/04/hlhhk4vtyz9vi4h5iwln.webp" style="float: left;width: 25px;display: inline-block;margin-right: 10px;margin-top: 0px;"/> Compartir
                        </button>
                    </div>
                <?php endforeach; ?>
            <?php else: ?>
                <p>No hay servicios registrados.</p>
            <?php endif; ?>
        </div>

        <!-- Paginador -->
        <div class="pagination" style="text-align: center; display: flex; justify-content: center;">
            <?php if ($pagina_actual > 1): ?>
            <a href="?pagina=<?= $pagina_actual - 1 ?>&categoria=<?= urlencode($categoria_filtro) ?>&titulo=<?= urlencode($titulo_filtro) ?>&fecha_inicio=<?= urlencode($fecha_inicio) ?>&fecha_fin=<?= urlencode($fecha_fin) ?>" class="item_pagination">Anterior</a>
            <?php endif; ?>

            <?php for ($i = 1; $i <= $total_paginas; $i++): ?>
            <a href="?pagina=<?= $i ?>&categoria=<?= urlencode($categoria_filtro) ?>&titulo=<?= urlencode($titulo_filtro) ?>&fecha_inicio=<?= urlencode($fecha_inicio) ?>&fecha_fin=<?= urlencode($fecha_fin) ?>" class="item_pagination <?= $i === $pagina_actual ? 'active' : '' ?>"><?= $i ?></a>
            <?php endfor; ?>

            <?php if ($pagina_actual < $total_paginas): ?>
            <a href="?pagina=<?= $pagina_actual + 1 ?>&categoria=<?= urlencode($categoria_filtro) ?>&titulo=<?= urlencode($titulo_filtro) ?>&fecha_inicio=<?= urlencode($fecha_inicio) ?>&fecha_fin=<?= urlencode($fecha_fin) ?>" class="item_pagination">Siguiente</a>
            <?php endif; ?>
        </div>
    </div>
    <script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>

    <script>
        const categorias ={
            "Paquete de servicios" : [
                "Rescata tu Melena",
                "Mima tu Melena Chiquita",
                "Empecemos desde 0",
                "Engalana tu Melena natural",
                "Curly Men Pack"
            ],
            "Cortes de cabello" : [
                "Capas cortas",
                "Capas largas",
                "Fade Plus",
                "Fade Plus con diseño",
                "Corte con máquina standard",
                "Corte con máquina standard con diseño",
                "Pixie",
                "Curly Bob asimétrico",
                "Curly Bob francés",
                "Shaggy",
                "Flequillo"
            ],
            "Trenzas" : [],
            "Color e iluminaciones" : [
                "Color uniforme",
                "Cobertura de canas",
                "Mechas creativas",
                "Balayage"
            ],
            "Peinados para eventos" : [],
            "Tratamiento intenso de uso profesional" : [],
            "Rizado permanente en cabello liso" : [],
            "Patrón de cabello" : [],
            "Hidratación profunda de uso profesional" : [
                "Células Madres - Alfaparf",
                "Restructure Morphosis - Framesi",
                "Fusio Dose - Kérastase"
            ]
        };
        function filtrarPorSubcategoria(subcategoria) {
            const urlParams = new URLSearchParams(window.location.search);
            urlParams.set('subcategoria', subcategoria);
            window.location.search = urlParams.toString();
        }
        function filtrarPorCategoria(categoria) {
           const validateSubcategorias = categorias[categoria];
           if (validateSubcategorias.length > 0) {
                let htmlswealert="";
                for (let i = 0; i < validateSubcategorias.length; i++) {
                    htmlswealert+=`<button class="button"  onclick="filtrarPorSubcategoria('${validateSubcategorias[i]}')">${validateSubcategorias[i]}</button>`;
                }
                Swal.fire({
                    title: 'Selecciona una subcategoría',
                    html: htmlswealert,
                    showCloseButton: true,
                });
           }else{
                const urlParams = new URLSearchParams(window.location.search);
                urlParams.set('categoria', categoria);
                window.location.search = urlParams.toString();
           }
        }
        function todoFilter(){
            const urlParams = new URLSearchParams(window.location.search);
            urlParams.delete('categoria');
            urlParams.delete('subcategoria');
            urlParams.delete('titulo');
            urlParams.delete('fecha_inicio');
            urlParams.delete('fecha_fin');
            window.location.search = urlParams.toString();
        }
        document.addEventListener('DOMContentLoaded', function () {
            
            
            const divContainerCategorias = document.getElementById('botones_categorias');
            const htmlButtons = Object.keys(categorias).map(categoria => {
                return `<button class="button" style="color:black;background:#e0e0e0" onclick="filtrarPorCategoria('${categoria}')">${categoria}</button>`;
            }).join('');
            divContainerCategorias.innerHTML =`<button class="button" style="color:black;background:#e0e0e0" onclick="todoFilter();">Todo</button>` + htmlButtons;
          
        });
    </script>
    <script>
        document.addEventListener('DOMContentLoaded', function () {
            const buttons = document.querySelectorAll('.compartir-btn');

            buttons.forEach(button => {
            button.addEventListener('click', async function () {
                const imageUrl = this.getAttribute('data-image');
                const title = this.getAttribute('data-title');
                const shareText = `Mira este servicio: ${title}`;

                try {
                const response = await fetch(imageUrl);
                const blob = await response.blob();
                const file = new File([blob], 'imagen.jpg', { type: blob.type });

                if (navigator.share) {
                    navigator.share({
                    title: 'Compartir Servicio',
                    text: shareText,
                    files: [file],
                    })
                    .then(() => console.log('Compartido con éxito'))
                    .catch((error) => console.error('Error al compartir', error));
                } else {
                    alert('La función de compartir no está disponible en este navegador.');
                }
                } catch (error) {
                console.error('Error al obtener la imagen', error);
                alert('No se pudo compartir la imagen.');
                }
            });
            });
        });
    </script>

    <style>
        .button{
            border-radius:50px;
            margin: 5px;
            background-color: #e5257e; 
            color: white;
            border-color: black;
        }
        .buscador-categoria {
            margin-bottom: 20px;
        }
        .buscador-categoria input {
            padding: 5px;
            width: 200px;
        }
        .cards-container {
            margin-top: 1em;
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(250px, 1fr)); /* Grid ajustable */
            gap: 20px;
            justify-content: space-around;
            align-items: stretch; /* Asegura que todas las tarjetas tengan la misma altura */
        }

        .card {
            display: flex;
            flex-direction: column; /* Asegura que los elementos estén en columna */
            justify-content: space-between; /* Espacia el contenido uniformemente */
            border: 1px solid #ddd;
            border-radius: 5px;
            padding: 0 0 10px 0;
            width: 100%;
            max-width: 350px;
            text-align: center;
            box-shadow: 0 2px 5px rgba(0, 0, 0, 0.1);
            height: 100%; /* Asegura que todas las tarjetas tengan la misma altura */
        }

        .card-image {
            max-width: 100%;
            height: auto;
            border-radius: 5px;
        }

        .card-title {
            font-size: 20px;
            margin: 10px 0;
            color: rgb(39, 171, 114);
            flex-grow: 1; /* Permite que el título ocupe espacio adicional si es necesario */
        }

        .compartir-btn {
            background-color: #e5257e;
            color: #fff;
            border: none;
            padding: 10px;
            cursor: pointer;
            border-radius: 5px;
            margin-top: 10px;
        }

        .compartir-btn:hover {
            background-color: #005177;
        }
        .item_pagination{
            margin-right:10px
        }

        @media screen and (max-width: 600px) {
            .cards-container {
                grid-template-columns: 1fr; /* Una columna en pantallas pequeñas */
            }
            .card {
                width: 100%;
                max-width: 350px;
            }
        }
    </style>
    <?php
    return ob_get_clean();
}

// Registrar la ruta de la API
add_action('rest_api_init', function () {
    register_rest_route('catalogo-servicios/v1', '/servicios', [
        'methods' => 'GET',
        'callback' => 'obtener_servicios_api',
        'permission_callback' => '__return_true', // Permitir acceso público
    ]);
});

// Función para obtener los servicios
function obtener_servicios_api($data) {
    global $wpdb;
    $tabla = $wpdb->prefix . 'servicios';

    // Obtener parámetros opcionales de la solicitud
    $categoria = isset($data['categoria']) ? sanitize_text_field($data['categoria']) : '';
    $titulo = isset($data['titulo']) ? sanitize_text_field($data['titulo']) : '';

    // Construir la consulta
    $query = "SELECT * FROM $tabla WHERE 1=1";
    $query_params = [];

    if (!empty($categoria)) {
        $query .= " AND categoria = %s";
        $query_params[] = $categoria;
    }

    if (!empty($titulo)) {
        $query .= " AND titulo LIKE %s";
        $query_params[] = '%' . $wpdb->esc_like($titulo) . '%';
    }

    $query .= " ORDER BY fecha DESC";

    // Ejecutar la consulta
    $servicios = $wpdb->get_results($wpdb->prepare($query, $query_params));

    // Formatear la respuesta
    $respuesta = [];
    foreach ($servicios as $servicio) {
        $respuesta[] = [
            'id' => $servicio->id,
            'titulo' => $servicio->titulo,
            'categoria' => $servicio->categoria,
            'subcategoria' => $servicio->subcategoria,
            'imagen' => $servicio->imagen,
            'fecha' => $servicio->fecha,
        ];
    }

    return rest_ensure_response($respuesta);
}

// Registrar la ruta de la API para obtener categorías
add_action('rest_api_init', function () {
    register_rest_route('catalogo-servicios/v1', '/categorias', [
        'methods' => 'GET',
        'callback' => 'obtener_categorias_api',
        'permission_callback' => '__return_true', // Permitir acceso público
    ]);
});

// Función para obtener las categorías
function obtener_categorias_api() {
    // Definir las categorías y subcategorías
    $categorias = [
        "Paquete de servicios" => [
            "Rescata tu Melena",
            "Mima tu Melena Chiquita",
            "Empecemos desde 0",
            "Engalana tu Melena natural",
            "Curly Men Pack"
        ],
        "Cortes de cabello" => [
            "Capas cortas",
            "Capas largas",
            "Fade Plus",
            "Fade Plus con diseño",
            "Corte con máquina standard",
            "Corte con máquina standard con diseño",
            "Pixie",
            "Curly Bob asimétrico",
            "Curly Bob francés",
            "Shaggy",
            "Flequillo"
        ],
        "Trenzas" => [],
        "Color e iluminaciones" => [
            "Color uniforme",
            "Cobertura de canas",
            "Mechas creativas",
            "Balayage"
        ],
        "Peinados para eventos" => [],
        "Tratamiento intenso de uso profesional" => [],
        "Rizado permanente en cabello liso" => [],
        "Patrón de cabello" => [],
        "Hidratación profunda de uso profesional" => [
            "Células Madres - Alfaparf",
            "Restructure Morphosis - Framesi",
            "Fusio Dose - Kérastase"
        ]
    ];

    return rest_ensure_response($categorias);
}