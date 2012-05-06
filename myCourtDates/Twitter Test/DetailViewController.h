//
//  DetailViewController.h
//  Twitter Test
//
//  Created by Lasse Bunk on 1/8/12.
//  Copyright (c) 2012 __MyCompanyName__. All rights reserved.
//

#import <UIKit/UIKit.h>

@interface DetailViewController : UIViewController {
    IBOutlet UILabel *captionLabel;
    IBOutlet UILabel *settingLabel;
    IBOutlet UILabel *locationLabel;
}

@property (strong, nonatomic) id detailItem;

- (IBAction)loadDocketPage:(id)sender;

@end
