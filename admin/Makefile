make:
	lessc --source-map=static/css/style.css.map --clean-css static/css/style.less static/css/style.css

watch:
	inotifywait -m -e CLOSE_WRITE static/css/style.less | \
	while read FILE EVENT                        ; \
	do                                             \
		echo $$EVENT | grep WRITE && make          ; \
	done                                         ; \
