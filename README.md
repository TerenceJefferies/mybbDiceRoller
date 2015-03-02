# mybbDiceRoller
MyBB Dice Roller

A simple plugin for MyBB that allows users to create a dice roll using a multi-sided dice and with multiple rolls per entry.

Features:

Ability to enable/disable the feature using custom settings
Ability verify rolls (to ensure the rolls have not been fabricated) - Supply the settings page with a custom SALT which used used in conjunction with the post ID to create a secure sha1 string, which can then be manually created and compared for verification
Ability to hide/show verification rolls

Usage:

To create a new roll append a string in the following format to a post:

[roll <RollName> <number of rolls>d<number of sides on dice>]

Example:
[roll TestRoll 1d10] - Roll a dice with the name "TestRoll" 1 time, the dice has 10 sides
[roll TestRoll2 3d12] - Roll a dice with the name "TestRoll2" 3 times, the dice has 12 sides

Names have been assigned to rolls to ensure users cannot apply higher/preferable rolls to preferable scenarios. Eg. Assigning a lower dice roll to ensure a better outcome

Rules:

- Rolls cannot have the same name, and the latest roll with the same name will overwrite the previous one.
- Roll names cannot have spaces in them
