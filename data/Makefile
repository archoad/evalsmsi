LATEX = pdflatex
OPTION = --file-line-error
IMPORT = header.tex intro.tex methode.tex
SRC = $(wildcard *.tex)
TEX = $(filter-out $(IMPORT),$(SRC))
PDF = $(patsubst %.tex,%.pdf, $(TEX))
OPTIONGREP = -e ^Overfull -e ^Underfull -e Warning --color

export max_print_line=1000
export error_line=254
export half_error_line=238

%.pdf : %.tex # règle pour créer un pdf à partir d'un tex
	@$(LATEX) $(OPTION) $<
	@$(LATEX) $(OPTION) $<
	@$(LATEX) $(OPTION) $< | grep $(OPTIONGREP)

all : $(PDF)

clean :
	-@rm *.aux
	-@rm *.log
	-@rm *.out
	-@rm *.toc
	-@rm *.xmpdata
	-@rm *.xmpi

cleanpdf :
	-@rm *.pdf
