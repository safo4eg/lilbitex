server {
    listen 80;
    server_name localhost;

    root /var/www/public;
    index index.php index.html;

    location / {
        try_files $uri $uri/ /index.php?$query_string;
    }

    location ~ \.php$ {
        try_files $uri =404;
        fastcgi_split_path_info ^(.+\.php)(/.+)$;
        fastcgi_pass app:9000;
        fastcgi_index index.php;
        include fastcgi_params;
        fastcgi_param SCRIPT_FILENAME $document_root$fastcgi_script_name;
        fastcgi_param PATH_INFO $fastcgi_path_info;
    }

    # Защита скрытых файлов и директорий
    location ~ /\.ht {
        deny all;
    }

    # Защита файла .env
    location ~ /\.env {
        deny all;
    }

    # Защита папки storage
    location ~ ^/storage/(.*) {
        deny all;
    }
}