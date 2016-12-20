make:
	lessc --source-map=css/style.css.map --clean-css css/style.less css/style.css

watch:
	inotifywait -m -e CLOSE_WRITE css/style.less | \
	while read FILE EVENT                        ; \
	do                                             \
		echo $$EVENT | grep WRITE && make          ; \
	done                                         ; \
