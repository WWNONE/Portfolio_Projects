CC = gcc
FLAGS = -Wall -Wextra
OBJ_FILES = main.o
EXE_FILE = cache_exec

.PHONY: all clean

all: $(EXE_FILE)

$(EXE_FILE): $(OBJ_FILES)
	$(CC) $(FLAGS) -o $@ $^ -lm

main.o: main.c cache.h
	$(CC) $(FLAGS) -c $< -o $@

clean:
	rm -f $(OBJ_FILES) $(EXE_FILE)
