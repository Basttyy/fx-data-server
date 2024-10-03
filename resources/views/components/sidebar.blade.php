<?php $request_uri = url()->current(false); ?>
{% if( !str_contains($request_uri, "/devops/login")): %}
  <div class="col sidebar mb-3">
    <h1><i class="fa fa-calendar" aria-hidden="true"></i> {{ $title }}</h1>
    <p class="text-muted"><i>by basttyy</i></p>

    {% include 'components/dark_mode_switch.blade.php' %}

    <div class="list-group div-scroll">
      {% if( str_contains($request_uri, "/devops/error-logs")): %}
        {% foreach($folders as $folder): %}
            <div class="list-group-item">
            <?php
            \Basttyy\FxDataServer\libs\LogViewer\LogViewer::DirectoryTreeStructure( $storage_path, $structure );
            ?>

            </div>
        {% endforeach; %}
        {% foreach($files as $file): %}
            <a href="?l={{ encrypt($file) }}"
                class="list-group-item {% if ($current_file == $file): %} llv-active {% endif; %}">
            {{$file}}
            </a>
        {% endforeach; %}
      {% endif; %}
    </div>
  </div>
{% endif; %}
