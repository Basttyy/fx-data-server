{% extends header.blade.php %}

{% block title %}{{ $title }}{% endblock %}
{% block content %}

<div class="container-fluid">
  <div class="row">
    {% include 'components/sidebar.blade.php' %}
    <div class="col-10 table-container">
      {% if ($logs === null): %}
        <div>
          Log file >50M, please download it.
        </div>
      {% else: %}
        <table id="table-log" class="table table-striped" data-ordering-index="{{ $standardFormat ? 2 : 0 }}">
          <thead>
          <tr>
            {% if ($standardFormat): %}
              <th>Level</th>
              <th>Context</th>
              <th>Date</th>
            {% else: %}
              <th>Line number</th>
            {% endif; %}
            <th>Content</th>
          </tr>
          </thead>
          <tbody>

          {% foreach($logs as $key => $log): %}
            <tr data-display="stack{{{$key}}}">
              {% if ($standardFormat): %}
                <td class="nowrap text-{{{$log['level_class']}}}">
                  <span class="fa fa-{{{$log['level_img']}}}" aria-hidden="true"></span>&nbsp;&nbsp;{{$log['level']}}
                </td>
                <td class="text">{{$log['context']}}</td>
              {% endif; %}
              <td class="date">{{$log['date']}}</td>
              <td class="text">
                {% if ($log['stack']): %}
                  <button type="button"
                          class="float-right expand btn btn-outline-dark btn-sm mb-2 ml-2"
                          data-display="stack{{{$key}}}">
                    <span class="fa fa-search"></span>
                  </button>
                {% endif; %}
                {{$log['text']}}
                {% if (isset($log['in_file'])): %}
                  <br/>{{$log['in_file']}}
                {% endif; %}
                {% if ($log['stack']): %}
                  <div class="stack" id="stack{{{$key}}}"
                       style="display: none; white-space: pre-wrap;">{{{ trim($log['stack']) }}}
                  </div>
                {% endif; %}
              </td>
            </tr>
            {% endforeach; %}

          </tbody>
        </table>
      {% endif; %}
      <div class="p-3">
        {% if($current_file): %}
          <a href="?dl={{ encrypt($current_file) }}{{ ($current_folder) ? '&f=' . encrypt($current_folder) : '' }}">
            <span class="fa fa-download"></span> Download file
          </a>
          -
          <a id="clean-log" href="?clean={{ encrypt($current_file) }}{{ ($current_folder) ? '&f=' . encrypt($current_folder) : '' }}">
            <span class="fa fa-sync"></span> Clean file
          </a>
          -
          <a id="delete-log" href="?del={{ encrypt($current_file) }}{{ ($current_folder) ? '&f=' . encrypt($current_folder) : '' }}">
            <span class="fa fa-trash"></span> Delete file
          </a>
          {% if(count($files) > 1): %}
            -
            <a id="delete-all-log" href="?delall=true{{ ($current_folder) ? '&f=' . encrypt($current_folder) : '' }}">
              <span class="fa fa-trash-alt"></span> Delete all files
            </a>
          {% endif; %}
        {% endif; %}
      </div>
    </div>
  </div>
</div>
<script>
  $(document).ready(function () {
    $('.table-container tr').on('click', function () {
      $('#' + $(this).data('display')).toggle();
    });
    $('#table-log').DataTable({
      "order": [$('#table-log').data('orderingIndex'), 'desc'],
      "stateSave": true,
      "stateSaveCallback": function (settings, data) {
        window.localStorage.setItem("datatable", JSON.stringify(data));
      },
      "stateLoadCallback": function (settings) {
        var data = JSON.parse(window.localStorage.getItem("datatable"));
        if (data) data.start = 0;
        return data;
      }
    });
    $('#delete-log, #clean-log, #delete-all-log').click(function () {
      return confirm('Are you sure?');
    });
  });
</script>
{% endblock %}
