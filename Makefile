.PHONY: up down logs shell reset
up:
	docker compose up -d --build
down:
	docker compose down
logs:
	docker compose logs -f --tail=200 app
shell:
	docker compose exec app bash
reset:
	docker compose down -v
	docker compose up -d --build
	@echo "Esperando seed..."
	sleep 15
	docker compose logs app --tail=80
	docker exec -it pop-estate-db-1 psql -U pop -d pop_estate -c "SELECT 'app_user' as tabla, count(*) FROM app_user UNION ALL SELECT 'company', count(*) FROM company UNION ALL SELECT 'owner', count(*) FROM owner UNION ALL SELECT 'property', count(*) FROM property UNION ALL SELECT 'settlement', count(*) FROM settlement;"
