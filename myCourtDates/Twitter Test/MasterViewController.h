//
//  MasterViewController.h
//  Twitter Test
//
//  Created by Lasse Bunk on 1/8/12.
//  Copyright (c) 2012 __MyCompanyName__. All rights reserved.
//

#import <UIKit/UIKit.h>

@interface MasterViewController : UITableViewController {
    NSArray *events;
}
@property NSString *barNumber;
- (void)fetchEvents;

@end
