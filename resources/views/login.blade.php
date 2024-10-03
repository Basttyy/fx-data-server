{% extends 'header.blade.php' %}

{% block title %}Login{% endblock %}

{% block content %}
<div class="container-fluid">
  <div class="row justify-content-center">
    <div class="col-md-6">
      <h1 class="text-center"><i class="fa fa-sign-in" aria-hidden="true"></i> Login</h1>
      <p class="text-muted text-center"><i>by basttyy</i></p>

      {% include 'components/dark_mode_switch.blade.php' %}

      <form method="POST" action="/devops/login">
        {% if (isset($_SESSION['error'])): %}
          <div class="alert alert-danger">
            {{ $_SESSION['error'] }}
            <?php unset($_SESSION['error']); ?>
          </div>
        {% endif; %}
        <div class="form-group">
          <label for="email">Email address</label>
          <input type="email" class="form-control" id="email" name="email" required>
        </div>
        <div class="form-group">
          <label for="password">Password</label>
          <input type="password" class="form-control" id="password" name="password" required>
        </div>
        <button type="submit" class="btn btn-primary btn-block">Login</button>
      </form>
      
      <div class="mt-3">
        <p class="text-center">
          Don't have an account? <a href="/devops/register">Register here</a>
        </p>
        <p class="text-center">
          Forgot your password? <a href="/devops/password/reset">Reset it here</a>
        </p>
      </div>
    </div>
  </div>
</div>

<script>
</script>
{% endblock %}
