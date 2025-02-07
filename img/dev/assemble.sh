#/bin/bash

convert saphir/1_r.png saphir/2_r.png saphir/3_r.png saphir/4_r.png saphir/5_r.png saphir/6_r.png dos_r.png saphir/8_r.png saphir/9_r.png saphir/10_r.png +append saphir.png
convert rubis/1_r.png rubis/2_r.png rubis/3_r.png rubis/4_r.png rubis/5_r.png rubis/6_r.png dos_r.png rubis/8_r.png rubis/9_r.png rubis/10_r.png +append rubis.png
convert dos_r.png dos_r.png dos_r.png dos_r.png dos_r.png dos_r.png e_7_r.png dos_r.png dos_r.png dos_r.png  +append emeraude.png
convert dos_r.png dos_r.png dos_r.png dos_r.png dos_r.png dos_r.png d_7_r.png dos_r.png dos_r.png dos_r.png  +append diamond.png

convert saphir.png rubis.png emeraude.png diamond.png -append ../dev_cards.png
