FROM amazonlinux:2023
ENV container docker
USER root

# install postgres

RUN dnf -y install postgresql15 postgresql15-server postgresql15-contrib postgresql15-server-devel postgresql15-private-devel vim sudo
RUN dnf -y update
RUN dnf -y install gcc make

#RUN mkdir /var/lib/pgsql
RUN chown postgres:postgres /var/lib/pgsql

USER postgres
RUN initdb -D "/var/lib/pgsql/data"

#swap back to root for entry point and gmake
USER root

ENV POSTGRES_PASSWORD=canopy \
    POSTGRES_USER=canopy \
    POSTGRES_DB=canopy

ADD docker_postgres/db_script.sh /
RUN ./db_script.sh

# Allow any host to connect to postgres_db
RUN echo -e "host \t all \t all \t all \t md5" >> /var/lib/pgsql/data/pg_hba.conf
RUN echo "listen_addresses = '*'" >> /var/lib/pgsql/data/postgresql.conf

ADD docker_postgres/docker-postgres-entry.sh /docker-entry.sh
ENTRYPOINT ["/docker-entry.sh"]
