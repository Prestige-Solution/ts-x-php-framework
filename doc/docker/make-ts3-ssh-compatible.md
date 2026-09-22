# TeamSpeak 3 and TeamSpeak 6 SSH Compatibility

## TeamSpeak 3 Server
### docker-compose.yml
```yaml
services:
  teamspeak:
    image: teamspeak:latest
    container_name: teamspeak-server
    ports:
      - "9987:9987/udp"   # Voice
      - "10011:10011"     # ServerQuery
      - "10022:10022"     # SSH ServerQuery (if supported by binary)
      - "30033:30033"     # File transfer
    volumes:
      - ./data:/var/ts3server   # Persistent data directory created automatically
    environment:
      TS3SERVER_LICENSE: accept
      TS3SERVER_QUERY_PROTOCOLS: "raw,ssh"
      TS3SERVER_QUERY_SSH_PORT: "10022"
      TS3SERVER_SERVERADMIN_PASSWORD: abc123
      # If using a separate PostgreSQL database:
      #TS3SERVER_DB_PLUGIN: ts3db_postgresql
      #TS3SERVER_DB_HOST: '127.0.0.1'
      #TS3SERVER_DB_USER: 'query user'
      #TS3SERVER_DB_PASSWORD: 'query user password' # Can be updated at any time; applied during server startup.
      #TS3SERVER_DB_NAME: 'database name'
      #TS3SERVER_DB_PORT: 5432   # Optional, default: 5432
    restart: unless-stopped
```

### Set Up an RSA Host Key (`ssh_host_rsa_key`)
Navigate to `ts3-docker/data` and generate the RSA key pair:
```shell
ssh-keygen -t rsa -b 4096 -m PEM -f ssh_host_rsa_key -N ""
```
This creates a compatible RSA host key for the TeamSpeak 3 server.

Start the server using `docker-compose up -d`. The logs should not show `creating QUERY_SSH_RSA_HOST_KEY file…`.

Ensure that appropriate file permissions are set for the key files:
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

## TeamSpeak 6 Server
```yaml
services:
  teamspeak:
    image: teamspeaksystems/teamspeak6-server:latest
    container_name: teamspeak-server
    restart: unless-stopped
    ports:
      - "9987:9987/udp"    # Default voice port
      - "30033:30033/tcp"  # File transfer port
      - "10022:10022/tcp"  # (Optional) ServerQuery SSH port
      - "10080:10080/tcp"  # (Optional) WebQuery port
      - "5899:5899"        # WebSocket
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
