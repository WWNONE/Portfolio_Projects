#!/bin/bash

# PART 1 -------------------------------------

# Clean up and compile environment
echo "Cleaning up environment and compiling..."
make clean
make all
clear

# Fixed arguments
cache_layers='1'
empty_layer='0'
print_style='1'

# Trace file setup
tracer=('126.gcc' '129.compress' '132.ijpeg' '134.perl' '099.go' '124.m88ksim')

# Array loops
cache_types=('U' 'I' 'D')
cache_sizes=('8' '16')
line_sizes=('4' '8')

echo "Starting cache configuration tests..."

# Loop over every permutation of tracer
for tracer in "${tracer[@]}"; do
  tracer_path="../traces/$tracer"  # Concatenate the path to the tracer file
  echo -e "\nXXXXXXXXXXXXXXXXXXXXXXXXXXXXXXXXXXXXXXXXXXXXXXXXXXXXXXXXXXXXXXXXXXXXX\n"
  echo -e "\t\t\tTesting trace $tracer..."
  echo -e "\nXXXXXXXXXXXXXXXXXXXXXXXXXXXXXXXXXXXXXXXXXXXXXXXXXXXXXXXXXXXXXXXXXXXXX\n"
  for type in "${cache_types[@]}"; do
    echo -e "================================================================\n"
    for size in "${cache_sizes[@]}"; do
      for line in "${line_sizes[@]}"; do
        echo -e "------------------------------------------------------------"
        echo -e "Configuration: Type $type, Cache Size ${size}kb, Line Size ${line} words"
        echo -e "------------------------------------------------------------"
        ./cache_exec $type $line $cache_layers $size $empty_layer $empty_layer $print_style < $tracer_path
        echo -e "------------------------------------------------------------\n"
      done
    done
  done
done

echo "============================================================"
echo -e "\n\t\t\tALL FINISHED!\n"
echo -e "============================================================\n"
