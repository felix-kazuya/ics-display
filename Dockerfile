FROM iteconomics/apache
WORKDIR /tmp
RUN git clone https://github.com/felix-kazuya/ics-display.git && mv /tmp/ics-display/src/* /var/www/html/
WORKDIR /var/www/html
RUN composer install
#it's not finally build, but I really like this tool
