export const CraftingWalkthroughStages = (t: (key: string) => string) => [
  {
    id: '#inventoryTrigger',
    title: t('Inventory'),
    content: t(
      'Here you can add your owned items, either tradable or non-tradable and they will be counted for the total result of the recipe you are trying to calculate.'
    ),
  },
  {
    id: '#itemSelectorFrame',
    title: t('Select your item recipe'),
    content: t(
      "That's the main item frame where you can control the selected recipe."
    ),
  },
  {
    id: '#recipeSubitems',
    title: t('Recipe cost'),
    content: t(
      'Here you can edit the values below each item to your owned amount of them, this is useful for subtracting the total cost and seeing how many items are left.'
    ),
  },
  {
    id: '#recipeSubitems>*>*',
    title: t('Recipe item'),
    content: t(
      "Here you can choose how much you have of this item so it can be subtracted from the total cost result on the bottom of the page. \n\n You can also hover over the item, and if a recipe is available to it, you'll see a popup with the required items. (Your inventory affects this recipe)"
    ),
  },
  {
    id: '#totalCostPanel',
    title: t('Total cost panel'),
    content: t(
      "Here's where you can see the result of a calculation, this can be affected by your inventory items or rarity items visibility settings. \n\n Remember you can hover over abstract numbers to see their specific values"
    ),
  },
  {
    id: '#totalCostWithRarity',
    title: t('Cost of rarity items'),
    content: t(
      'This left panel shows the cost of items with rarity, this can be a result of the necessary cost of base items for your recipe. \n\n For example, this panel can account for the necessary items for crafting rare steel for a rare weapon.'
    ),
  },
  {
    id: '#totalCostWithoutRarity',
    title: t('Cost of generic items'),
    content: t(
      "This right panel shows the generic items for recipe crafting, this is the sum of ALL item's cost of copper for example. \n\n Note that this is going, to sum up the cost of base resources, secondary, tertiary, and so on."
    ),
  },
]

export const ExperienceWalkthroughStages = (t: (key: string) => string) => [
  {
    id: '#experienceTimer',
    title: t('Experience gain measure'),
    content: t(
      'You can start a five minutes timer and start measuring your performance, make sure to mark the start experience percentage and insert the final percentage when the times end up.'
    ),
  },
  {
    id: '#percentageDifference',
    title: t('Percentage difference'),
    content: t(
      'Here you can use the measurment by percentages which you need to use the timer, or you can use the second two inputs if you know your gain per minute.'
    ),
  },
  {
    id: '#experienceTimerInput',
    title: t('Input by percentages'),
    content: t(
      'The use of the 5 minutes timer is required. Recommended if is the first time measuring your performance.'
    ),
  },
  {
    id: '#experienceXPRateInput',
    title: t('Input by experience rate'),
    content: t(
      'Recommended for users who have already measured experience gainings or if you already know the xp amount earned per minute.'
    ),
  },
  {
    id: '#experienceLevels',
    title: t('Level and performance section'),
    content: t(
      "Insert your current and desired level, when the necessary fields are correctly filled, you'll be able to see:\n\nRemaining XP to the desired level. (accounts for your current percentage)\nThe amount of time needed to reach your desired level.\nYour XP gains every 5 minutes."
    ),
  },
  {
    id: '#experienceSquareAndPeak',
    title: t('Magic Square and Secret Peak'),
    content: t(
      'Enter your tickets and your XP earned per run to calculate the time taken to level up paired with peak/square runs.'
    ),
  },
  {
    id: '#experienceVigor',
    title: t('Vigor'),
    content: t(
      'Enter your remaining vigor in hours to calculate your XP and percentage earned during an active vigor.'
    ),
  },
]
