# Teamspeak 3 and Teamspeak 6 SSH Compatible
## Teamspeak 3 Server
### docker-compose.yml
```yaml
services:
  teamspeak:
    image: teamspeak:latest
    container_name: teamspeak-server
    ports:
      - "9987:9987/udp"   # Voice
      - "10011:10011"     # Serverquery
      - "10022:10022"     # ssh query if binary support
      - "30033:30033"     # Filetransfer
    volumes:
      - ./data:/var/ts3server   # Persistente Daten creates automatically
    environment:
      TS3SERVER_LICENSE: accept
      TS3SERVER_QUERY_PROTOCOLS: "raw,ssh"
      TS3SERVER_QUERY_SSH_PORT: "10022"
      TS3SERVER_SERVERADMIN_PASSWORD: abc123
      #if you would use a seperate database with postgres
      #TS3SERVER_DB_PLUGIN: ts3db_postgresql
      #TS3SERVER_DB_HOST: '127.0.0.1'
      #TS3SERVER_DB_USER: 'query user'
      #TS3SERVER_DB_PASSWORD: 'query user password' #You can set this option at anytime. During start the password will be changed during server start.
      #TS3SERVER_DB_NAME: 'database name'
      #TS3SERVER_DB_PORT: 5432   # optional, Standard: 5432
    restart: unless-stopped
```

### Setup a ssh_rsa_host_key
go to ``ts3-docker/data`` and run ``ssh-keygen -t rsa -b 4096 -m PEM -f ssh_host_rsa_key -N ""`` <br>
This will create a compatible ssh_rsa_host_key for the teamspeak 3 server. <br>

Start the server with ``docker-compose up -d``. The logs should not see ``creating QUERY_SSH_RSA_HOST_KEY file…`` <br>
Be sure the correct permissions are set for the ssh_rsa_host_key file.
```shell
docker-compose up -d ts3
docker exec -it teamspeak-server sh -c "chmod 600 /var/ts3server/ssh_host_rsa_key && chmod 644 /var/ts3server/ssh_host_rsa_key.pub"
docker-compose restart ts3
```

### Directory Structure
```shell
.
├── data
│   ├── files
│   ├── logs
│   ├── query_ip_allowlist.txt
│   ├── query_ip_denylist.txt
│   ├── ssh_host_rsa_key
│   ├── ssh_host_rsa_key.pub
│   └── ts3server.sqlitedb
└── docker-compose.yml
```
## Teamspeak 6 Server
```yaml
services:
  teamspeak:
    image: teamspeaksystems/teamspeak6-server:latest
    container_name: teamspeak-server
    restart: unless-stopped
    ports:
      - "9987:9987/udp"    # Default voice port
      - "30033:30033/tcp"  # File transfer port
      - "10022:10022/tcp" # (Optional) ServerQuery SSH port
      - "10080:10080/tcp"  # (Optional) WebQuery port
      - "5899:5899" # Websocket
    environment:
      - TSSERVER_LICENSE_ACCEPTED=accept
      - TSSERVER_DEFAULT_PORT=9987
      - TSSERVER_VOICE_IP=0.0.0.0
      - TSSERVER_FILE_TRANSFER_PORT=30033
      - TSSERVER_FILE_TRANSFER_IP=0.0.0.0
      - TSSERVER_QUERY_HTTP_ENABLED=true
      - TSSERVER_QUERY_SSH_ENABLED=true
      # - TSSERVER_MACHINE_ID=my_unique_machine_id
      - TSSERVER_LOG_PATH=/var/tsserver/logs
      # - TSSERVER_QUERY_ADMIN_PASSWORD=secretpassword
    volumes:
      - tsserver-data:/var/tsserver

volumes:
  tsserver-data:
    name: tsserver-data
```
