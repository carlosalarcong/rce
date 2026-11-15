# Runner propio de GitHub Actions para el RCE (docs/ci.md). Ubuntu + el runner oficial de GitHub
# (verificado con su SHA-256) + el cliente de Docker, que maneja el Docker del equipo por su socket.
# Basado en el de acredita360.
# Versión fija: la última LTS estable. Al cambiarla, actualizar también runs-on en .github/workflows/ci.yml.
FROM ubuntu:26.04

ARG RUNNER_VERSION
ARG RUNNER_ARCH
ARG RUNNER_SHA256

ENV DEBIAN_FRONTEND=noninteractive
RUN apt-get update \
    && apt-get install -y --no-install-recommends ca-certificates curl git jq make sudo unzip gnupg tzdata \
    && install -m 0755 -d /etc/apt/keyrings \
    && curl -fsSL https://download.docker.com/linux/ubuntu/gpg -o /etc/apt/keyrings/docker.asc \
    && echo "deb [arch=$(dpkg --print-architecture) signed-by=/etc/apt/keyrings/docker.asc] https://download.docker.com/linux/ubuntu $(. /etc/os-release && echo "$VERSION_CODENAME") stable" > /etc/apt/sources.list.d/docker.list \
    && apt-get update \
    && apt-get install -y --no-install-recommends docker-ce-cli docker-buildx-plugin docker-compose-plugin \
    && rm -rf /var/lib/apt/lists/*

WORKDIR /actions-runner
RUN curl -fsSL -o runner.tar.gz "https://github.com/actions/runner/releases/download/v${RUNNER_VERSION}/actions-runner-${RUNNER_ARCH}-${RUNNER_VERSION}.tar.gz" \
    && echo "${RUNNER_SHA256}  runner.tar.gz" | sha256sum -c - \
    && tar xzf runner.tar.gz \
    && rm runner.tar.gz \
    && ./bin/installdependencies.sh \
    && rm -rf /var/lib/apt/lists/*

COPY entrypoint.sh /usr/local/bin/entrypoint.sh
RUN chmod +x /usr/local/bin/entrypoint.sh
# Corre como root: necesita el socket de Docker.
ENV RUNNER_ALLOW_RUNASROOT=1
ENTRYPOINT ["/usr/local/bin/entrypoint.sh"]
