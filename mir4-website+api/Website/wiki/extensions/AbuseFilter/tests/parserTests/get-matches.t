get_matches('I am a (dog|cat)', 'What did you say?') === [ false, false ] &
get_matches('The (truth|pineapple) is (?:rarely)? pure and (nee*v(ah|er) sh?imple)', 'The truth is rarely pure and never simple, Wilde said') == ['The truth is rarely pure and never simple', 'truth', 'never simple', 'er'] &
get_matches('You say (.*) \(and I say (.*)\)\.', 'You say hello (and I say goodbye).') === [ 'You say hello (and I say goodbye).', 'hello', 'goodbye' ] &
get_matches('I(?: am)? the ((walrus|egg man).*)\!', 'I am the egg man, I am the walrus !') === [ 'I am the egg man, I am the walrus !', 'egg man, I am the walrus ', 'egg man' ] &
get_matches('this (does) not match', 'foo bar') === [ false, false ]