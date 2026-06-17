FROM php:8.2-apache
# Usa uma imagem oficial do PHP com Apache já configurado

COPY . /var/www/html/
# Copia todos os arquivos do seu projeto para a pasta do servidor

RUN chown -R www-data:www-data /var/www/html
# Dá as permissões necessárias para o Apache ler os arquivos

EXPOSE 80
# Expõe a porta padrão que o Render utiliza