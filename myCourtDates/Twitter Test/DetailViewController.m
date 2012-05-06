//
//  DetailViewController.m
//  Twitter Test
//
//  Created by Lasse Bunk on 1/8/12.
//  Copyright (c) 2012 __MyCompanyName__. All rights reserved.
//

#import "DetailViewController.h"

@interface DetailViewController ()
- (void)configureView;
@end

@implementation DetailViewController

@synthesize detailItem = _detailItem;

#pragma mark - Managing the detail item

- (void)setDetailItem:(id)newDetailItem
{
    if (_detailItem != newDetailItem) {
        _detailItem = newDetailItem;
        
        // Update the view.
        [self configureView];
    }
}

- (void)configureView
{
    if (self.detailItem) {
        NSDictionary *event = self.detailItem;
        
        NSString *plnts = [event objectForKey:@"plaintiffs"];
        NSString *defs = [event objectForKey:@"defendants"];
        NSString *myCaption = [plnts capitalizedString];
        myCaption = [myCaption stringByAppendingString: @" v. "];
        myCaption = [myCaption stringByAppendingString:[defs capitalizedString]];
        captionLabel.text = myCaption;
        
        NSString *mySetting = [event objectForKey:@"setting"];
        settingLabel.text = mySetting;
        
        NSString *myLocation = [event objectForKey:@"location"];
        locationLabel.text = myLocation;

        // I think this code goes and gets the user's image on a separate thread and displays it once the image is downloaded
//        dispatch_async(dispatch_get_global_queue(DISPATCH_QUEUE_PRIORITY_DEFAULT, 0), ^{
//            NSString *imageUrl = [[event objectForKey:@"user"] objectForKey:@"profile_image_url"];
//            NSData *data = [NSData dataWithContentsOfURL:[NSURL URLWithString:imageUrl]];
//            
//            dispatch_async(dispatch_get_main_queue(), ^{
//                profileImage.image = [UIImage imageWithData:data];
//            });
//        });
    }
}

- (void)didReceiveMemoryWarning
{
    [super didReceiveMemoryWarning];
    // Release any cached data, images, etc that aren't in use.
}

#pragma mark - View lifecycle

- (void)viewDidLoad
{
    [super viewDidLoad];
	// Do any additional setup after loading the view, typically from a nib.
    [self configureView];
}

- (void)viewDidUnload
{
    [super viewDidUnload];
    // Release any retained subviews of the main view.
    // e.g. self.myOutlet = nil;
}

- (void)viewWillAppear:(BOOL)animated
{
    [super viewWillAppear:animated];
}

- (void)viewDidAppear:(BOOL)animated
{
    [super viewDidAppear:animated];
}

- (void)viewWillDisappear:(BOOL)animated
{
	[super viewWillDisappear:animated];
}

- (void)viewDidDisappear:(BOOL)animated
{
	[super viewDidDisappear:animated];
}

- (BOOL)shouldAutorotateToInterfaceOrientation:(UIInterfaceOrientation)interfaceOrientation
{
    // Return YES for supported orientations
    return (interfaceOrientation != UIInterfaceOrientationPortraitUpsideDown);
}

- (IBAction)loadDocketPage:(id)sender {
}
@end
