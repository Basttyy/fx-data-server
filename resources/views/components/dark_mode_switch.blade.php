<div class="custom-control custom-switch" style="padding-bottom:20px;">
  <input type="checkbox" class="custom-control-input" id="darkSwitch">
  <label class="custom-control-label" for="darkSwitch" style="margin-top: 6px;">Dark Mode</label>
</div>

<script>
  // Dark mode by https://github.com/coliff/dark-mode-switch
  const darkSwitch = document.getElementById('darkSwitch');

  // This is here so we can get the body dark mode before the page displays
  // otherwise the page will be white for a second... 
  initTheme();

  window.addEventListener('load', () => {
    if (darkSwitch) {
      initTheme();
      darkSwitch.addEventListener('change', () => {
        resetTheme();
      });
    }
  });

  // End darkmode js
</script>
