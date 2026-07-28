opnCentral v0.4.3.1

Fix:
- Keeps Debian trixie libzip5 as an explicit runtime dependency.
- Explicitly configures, installs and enables the PHP zip extension.
- Verifies ZipArchive before and after removing build dependencies.
- Docker image build now fails if ZipArchive is unavailable.
- Health check reports unhealthy if the ZIP extension is missing.
