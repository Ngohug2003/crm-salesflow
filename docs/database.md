# Database

The canonical ERD and rationale are in [architecture.md](architecture.md). PostgreSQL is the supported runtime database. Application domain tables use auto-incrementing `BIGINT` primary keys and indexed foreign keys; package tables retain their package-supported key format.
