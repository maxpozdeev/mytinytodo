To use mytinytodo you need a webserver and php installed.

User-frendly URLS in Apache
---------------------------
Put this in .htaccess file.
```
<IfModule mod_rewrite.c>
  RewriteEngine On
  RewriteCond %{REQUEST_FILENAME} !-f
  RewriteCond %{REQUEST_FILENAME} !-d
  RewriteRule ^api/.*$ api.php [L]
  RewriteCond %{REQUEST_FILENAME} !-f
  RewriteCond %{REQUEST_FILENAME} !-d
  RewriteRule . index.php [L]
</IfModule>
```

If you want use RESTfull requests enable additionals methods as well:
```
<Limit GET POST PUT DELETE>
  Allow from all
</Limit>
```

Then you can enable it in myTinyTodo via configuration directives.



Nginx config example
--------------------
```
server {
    listen 80;
    server_name _;
    root /var/www/html;
    index index.php;

    location ~ ^/(db|includes)/ {
        deny all;
    }

    location ~ /\.ht {
        deny all;
    }

    # Deny access to sensitive files in ext/
    location ~* ^/ext/.*\.(json|md)$ {
        deny all;
    }

    # API routing (optional)
    location /api/ {
        try_files $uri /api.php?$args;
    }

    # Pretty links (optional)
    location / {
        try_files $uri $uri/ /index.php?$query_string;
    }

    location ~ \.php$ {
        fastcgi_pass   127.0.0.1:9000;
        fastcgi_index  index.php;
        fastcgi_param  SCRIPT_FILENAME $document_root$fastcgi_script_name;
        include        fastcgi_params;
    }
}
```
