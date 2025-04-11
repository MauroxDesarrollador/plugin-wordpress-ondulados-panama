<?php
/*
Plugin Name: Catálogo de Servicios
Description: Plugin para gestionar un catálogo de servicios con imágenes
Version: 1.0
Author: Tu Nombre
*/

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
        subcategoria varchar(100) NOT NULL,
        imagen varchar(255) NOT NULL,
        fecha datetime DEFAULT CURRENT_TIMESTAMP NOT NULL,
        PRIMARY KEY  (id)
    ) $charset;";

    require_once(ABSPATH . 'wp-admin/includes/upgrade.php');
    dbDelta($sql);
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

    // Decodificar JSON
    $categorias = [];

    // Procesar formulario
    if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['nuevo_servicio'])) {
        $datos = array(
            'titulo' => sanitize_text_field($_POST['titulo']),
            'categoria' => sanitize_text_field($_POST['categoria']),
            'subcategoria' => sanitize_text_field($_POST['subcategoria']),
            'imagen' => esc_url_raw($_POST['imagen'])
        );

        $wpdb->insert($tabla, $datos);
        echo '<div class="notice notice-success"><p>Servicio agregado!</p></div>';
    }

    // Obtener servicios existentes
    $servicios = $wpdb->get_results("SELECT * FROM $tabla ORDER BY fecha DESC");

    // Mostrar interfaz
    ?>
    <div class="wrap">
        <div align="center" style="margin-bottom: 20px;text-align:center">
            <img src="https://mauricioreyesdev.com/wp-content/uploads/2025/04/logo-3fe76ad3-1.webp" style="width: 300px;margin-top: 2em;"/>
        </div>
        <h1>Catálogo de Servicios</h1>

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
                            <?php foreach ($categorias as $categoria): ?>
                                <option value="<?= esc_attr($categoria) ?>">
                                    <?= esc_html($categoria['attributes']['name']) ?>
                                </option>
                            <?php endforeach; ?>
                        </select>
                    </td>
                </tr>
                <tr>
                    <th><label>Subcategoría</label></th>
                    <td>
                        <select name="subcategoria" id="subcategoria" class="regular-text" required>
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
                "Paquete de servicios": [
                    "Rescata tu Melena",
                    "Mima tu Melena Chiquita",
                    "Empecemos desde 0",
                    "Engalana tu Melena natural",
                    "Curly Men Pack"
                ],
                "Cortes de cabello": [
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
                "Trenzas": [],
                "Color e iluminaciones": [
                    "Color uniforme",
                    "Cobertura de canas",
                    "Mechas creativas",
                    "Balayage"
                ],
                "Peinados para eventos": [],
                "Tratamiento intenso de uso profesional": [
                    "Rizado permanente en cabello liso",
                    "Patrón de cabello"
                ],
                "Hidratación profunda de uso profesional": [
                    "Células Madres - Alfaparf",
                    "Restructure Morphosis - Framesi",
                    "Fusio Dose - Kérastase"
                ]
            };

            const categoriaSelect = document.getElementById('categoria');
            const subcategoriaSelect = document.getElementById('subcategoria');

            // Evento para cargar subcategorías al cambiar la categoría
            categoriaSelect.addEventListener('change', function () {
                const categoriaSeleccionada = this.value;

                // Limpiar subcategorías
                subcategoriaSelect.innerHTML = '<option value="">Seleccionar Subcategoría</option>';

                // Cargar subcategorías correspondientes
                if (categorias[categoriaSeleccionada]) {
                    categorias[categoriaSeleccionada].forEach(subcategoria => {
                        const option = document.createElement('option');
                        option.value = subcategoria;
                        option.textContent = subcategoria;
                        subcategoriaSelect.appendChild(option);
                    });
                }
            });
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

    // Filtro por categoría
    $categoria_filtro = isset($_GET['categoria']) ? sanitize_text_field($_GET['categoria']) : '';

    // Consulta con filtro de categoría
    $query = "SELECT * FROM $tabla";
    $query_params = [];
    if (!empty($categoria_filtro)) {
        $query .= " WHERE categoria = %s";
        $query_params[] = $categoria_filtro;
    }
    $query .= " ORDER BY fecha DESC LIMIT %d OFFSET %d";
    $query_params[] = $por_pagina;
    $query_params[] = $offset;

    $servicios = $wpdb->get_results($wpdb->prepare($query, $query_params));

    // Contar total de servicios
    $total_servicios_query = "SELECT COUNT(*) FROM $tabla";
    if (!empty($categoria_filtro)) {
        $total_servicios_query .= " WHERE categoria = %s";
        $total_servicios = $wpdb->get_var($wpdb->prepare($total_servicios_query, $categoria_filtro));
    } else {
        $total_servicios = $wpdb->get_var($total_servicios_query);
    }
    $total_paginas = ceil($total_servicios / $por_pagina);

    // Generar HTML
    ob_start();
    ?>
    <div class="servicios-lista">
    <p style="color: rgb(39, 171, 114); text-align: center; width: 100%; font-size: 20px; font-weight: 700;" class="">Si encuentras tu resultado, comparte tu foto y menciónanos en Instagram como @ondulados.pa</p>
        <!-- Buscador por categoría -->
        <form method="get" class="buscador-categoria" style="text-align: center; margin-bottom: 20px;">
            <input style="height:45px" type="text" name="categoria" placeholder="Buscar por categoría" value="<?= esc_attr($categoria_filtro) ?>">
            <button type="submit" class="button">Buscar</button>
        </form>

        <!-- Mostrar servicios en formato de cards -->
        <div class="cards-container">
            <?php if ($servicios): ?>
                <?php foreach ($servicios as $servicio): ?>
                    <div class="card">
                        <img src="<?= esc_url($servicio->imagen) ?>" alt="<?= esc_attr($servicio->titulo) ?>" class="card-image">
                        <h3 class="card-title"><?= esc_html($servicio->titulo) ?></h3>
                        <button class="button compartir-btn" data-title="<?= esc_attr($servicio->titulo) ?>" data-image="<?= esc_url($servicio->imagen) ?>">Compartir</button>
                    </div>
                <?php endforeach; ?>
            <?php else: ?>
                <p>No hay servicios registrados.</p>
            <?php endif; ?>
        </div>

        <!-- Paginador -->
        <div class="pagination">
            <?php if ($pagina_actual > 1): ?>
                <a href="?pagina=<?= $pagina_actual - 1 ?>&categoria=<?= urlencode($categoria_filtro) ?>" class="button">Anterior</a>
            <?php endif; ?>

            <?php for ($i = 1; $i <= $total_paginas; $i++): ?>
                <a href="?pagina=<?= $i ?>&categoria=<?= urlencode($categoria_filtro) ?>" class="button <?= $i === $pagina_actual ? 'active' : '' ?>"><?= $i ?></a>
            <?php endfor; ?>

            <?php if ($pagina_actual < $total_paginas): ?>
                <a href="?pagina=<?= $pagina_actual + 1 ?>&categoria=<?= urlencode($categoria_filtro) ?>" class="button">Siguiente</a>
            <?php endif; ?>
        </div>
    </div>

    <script>
        document.addEventListener('DOMContentLoaded', function () {
            const buttons = document.querySelectorAll('.compartir-btn');

            buttons.forEach(button => {
                button.addEventListener('click', function () {
                    const imageUrl = this.getAttribute('data-image');
                    const title = this.getAttribute('data-title');
                    const shareText = `Mira este servicio: ${title}`;

                    if (navigator.share) {
                        navigator.share({
                            title: 'Compartir Servicio',
                            text: shareText,
                            url: imageUrl,
                        })
                        .then(() => console.log('Compartido con éxito'))
                        .catch((error) => console.error('Error al compartir', error));
                    } else {
                        alert('La función de compartir no está disponible en este navegador.');
                    }
                });
            });
        });
    </script>

    <style>
        .buscador-categoria {
            margin-bottom: 20px;
        }
        .buscador-categoria input {
            padding: 5px;
            width: 200px;
        }
        .cards-container {
            display: flex;
            flex-wrap: wrap;
            gap: 20px;
        }
        .card {
            border: 1px solid #ddd;
            border-radius: 5px;
            padding: 0 0 1em 0;
            width: 200px;
            text-align: center;
            box-shadow: 0 2px 5px rgba(0, 0, 0, 0.1);
        }
        .card-image {
            max-width: 100%;
            height: auto;
            border-radius: 5px;
        }
        .card-title {
            font-size: 18px;
            margin: 10px 0;
            color:rgb(39, 171, 114)
        }
        .compartir-btn {
            background-color: #e5257e;
            color: #fff;
            border: none;
            padding: 10px;
            cursor: pointer;
            border-radius: 5px;
        }
        .compartir-btn:hover {
            background-color: #005177;
        }
        .pagination .button {
            margin: 0 5px;
            text-decoration: none;
        }
        .pagination .button.active {
            font-weight: bold;
            text-decoration: underline;
        }
    </style>
    <?php
    return ob_get_clean();
}