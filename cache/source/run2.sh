#!/bin/bash

# PART 2 -------------------------------------

# Clean up and compile environment
echo "Cleaning up environment and compiling..."
make clean
make all
clear

# Fixed arguments
cache_type='D'
line_size='8'
print_style='2'

# Trace file setup
tracer=('126.gcc' '129.compress' '132.ijpeg' '134.perl' '099.go' '124.m88ksim')

# Layer counts
layers=('1' '2' '3')

# Cache sizes
L1_sizes=('4' '16')
L2_sizes=('32' '64')
L3_sizes=('256' '1024')

# Counter for configuration number
config_count=0
config_count_1=0

echo "Starting cache configuration tests..."

# Loop over every permutation of layer, cache size as needed
for tracer in "${tracer[@]}"; do
    config_count=0
    config_count_1=$((config_count_1 + 1))
    tracer_path="../traces/$tracer"  # Concatenate the path to the trace file
    echo -e "\nXXXXXXXXXXXXXXXXXXXXXXXXXXXXXXXXXXXXXXXXXXXXXXXXXXXXXXXXXXXXXXXXXXXXX\n"
    echo -e "\t\tTesting trace $tracer..."
    echo -e "\nXXXXXXXXXXXXXXXXXXXXXXXXXXXXXXXXXXXXXXXXXXXXXXXXXXXXXXXXXXXXXXXXXXXXX"
    for layer in "${layers[@]}"; do
        echo -e "\n====================================================================="
        echo "Testing configurations for Layer $layer..."
        echo -e "=====================================================================\n"
        if [[ "$layer" == "1" ]]; then
            for L1_size in "${L1_sizes[@]}"; do
                config_count=$((config_count + 1))
                echo -e "------------------------------------------------------------"
                echo -e "Configuration[$config_count_1-$config_count]: Layers $layer, L1 size ${L1_size}kb"
                echo -e "------------------------------------------------------------"
                ./cache_exec $cache_type $line_size $layer $L1_size 0 0 $print_style < "$tracer_path"
                echo -e "------------------------------------------------------------\n"
            done
        elif [[ "$layer" == "2" ]]; then
            for L1_size in "${L1_sizes[@]}"; do
                for L2_size in "${L2_sizes[@]}"; do
                    config_count=$((config_count + 1))
                    echo -e "------------------------------------------------------------"
                    echo -e "Configuration[$config_count_1-$config_count]: Layers $layer, L1 size ${L1_size}kb, L2 size ${L2_size}kb"
                    echo -e "------------------------------------------------------------"
                    ./cache_exec $cache_type $line_size $layer $L1_size $L2_size 0 $print_style < "$tracer_path"
                    echo -e "------------------------------------------------------------\n"
                done
            done
        elif [[ "$layer" == "3" ]]; then
            for L1_size in "${L1_sizes[@]}"; do
                for L2_size in "${L2_sizes[@]}"; do
                    for L3_size in "${L3_sizes[@]}"; do
                        config_count=$((config_count + 1))
                        echo -e "------------------------------------------------------------"
                        echo -e "Configuration[$config_count_1-$config_count]: Layers $layer, L1 size ${L1_size}kb, L2 size ${L2_size}kb, L3 size ${L3_size}kb"
                        echo -e "------------------------------------------------------------"
                        ./cache_exec $cache_type $line_size $layer $L1_size $L2_size $L3_size $print_style < "$tracer_path"
                        echo -e "------------------------------------------------------------\n"
                    done
                done
            done
        fi
    done
done

echo "============================================================"
echo -e "\n\t\t\tALL FINISHED!\n"
echo -e "============================================================\n"
