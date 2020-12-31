tinymvc_library_superuri.php
============================

Another uri helper that uses the uri library
In your controller, do something like this:
```
        $this->load->library('superuri');
        $this->superuri->init($this->uri);
        $this->view->assign('superuri', $this->superuri);
```

Then in your view you can use the ```$superuri``` variable to access the library methods.
