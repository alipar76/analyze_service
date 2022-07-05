<p align="center"><a href="https://laravel.com" target="_blank"><img src="https://hackr.io/tutorials/microservices/logo-microservices.svg?ver=1557508246" width="150"></a></p>

## Flight reservation system

This is a simple project about flight reservation system for practice microservice architecture.

### Services

- **[Main Service](https://github.com/SadeghSohani/main_service)**
- **[Authentication Service](https://github.com/SadeghSohani/authentication_service)**
- **[Analyze Service](https://github.com/alipar76/analyze_service)**

### Document

Please run the following commands to run services:

```
sudo docker network create main_net
sudo docker run --rm composer install
sudo docker compose up --build
```


